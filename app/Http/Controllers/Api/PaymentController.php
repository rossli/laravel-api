<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\PayLog;
use App\Models\SmsRecord;
use App\Models\User;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PaymentController extends BaseController
{
    private $app;

    public function __construct()
    {
        $config = [
            // 必要配置
            'app_id' => config('wechat.payment.default.app_id'),
            'mch_id' => config('wechat.payment.default.mch_id'),
            'key' => config('wechat.payment.default.key'),   // API 密钥
        ];

        $this->app = Factory::payment($config);
    }

    public function notify()
    {
        $response = $this->app->handlePaidNotify(function ($message, $fail) {
            // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
            //dd($message);
            $order = Order::where('status', 0)->where('order_sn', $message['out_trade_no'])->first();

            if (!$order || $order->paid_at) { // 如果订单不存在 或者 订单已经支付过了
                return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }

            $pay_log = PayLog::where('out_trade_no', $message['out_trade_no'])->first();
            if (!$pay_log || $pay_log->time_end) {
                return true; // 告诉微信，我已经处理完了，支付日志对不上,别再通知我了哈
            }
            $pay_log->bank_type = $message['bank_type'];
            $pay_log->cash_fee = $message['cash_fee'];
            $pay_log->fee_type = $message['fee_type'];
            $pay_log->is_subscribe = $message['is_subscribe'];
            $pay_log->openid = $message['openid'];
            $pay_log->transaction_id = $message['transaction_id'];
            $pay_log->result_code = $message['result_code'];
            $pay_log->return_code = $message['return_code'];
            if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                // 用户是否支付成功
                if (array_get($message, 'result_code') === 'SUCCESS') {
                    $order->paid_at = now(); // 更新支付时间为当前时间
                    $order->status = Order::STATUS_PAID;
                    $pay_log->time_end = $message['time_end'];

                    //处理课程归属
                    if ($order->type == Order::TYPE_NORMAL) {
                        event(new PaymentEvent($order));
                    }

                    //todo 给关注用户发送购买通知以及课程链接


                    // 用户支付失败
                } elseif (Arr::get($message, 'result_code') === 'FAIL') {
                    $order->status = Order::STATUS_PAID;
                    $pay_log->err_code = $message['err_code'];
                    $pay_log->err_code_des = $message['err_code_des'];
                }
            } else {
                return $fail('通信失败，请稍后再通知我');
            }

            $order->save(); // 保存订单
            $pay_log->save();
            return true; // 返回处理完成
        });

        return $response;

    }

    public function status($id)
    {
        $order = Order::with('orderItem', 'orderItem.book')->findOrFail($id);

        if (!$order || $order->paid_at) { // 如果订单不存在 或者 订单已经支付过了
          return $this->success('支付成功');
        }

        $res = $this->app->order->queryByOutTradeNumber($order->order_sn);

        $order_items = $order->orderItem;


        //这里方便本地测试
        \Log::info('pay_log:' . json_encode($res));

        if ($res['trade_state'] == 'SUCCESS') {
            $pay_log = PayLog::where('order_id', $order->id)->first();
            $order->paid_at = now(); // 更新支付时间为当前时间
            $order->status = Order::STATUS_PAID;
            $order->wait_pay_fee = 0;
            $order->has_paid_fee = $res['cash_fee'];
            $order->status = Order::STATUS_PAID;
            $order->pay_log_id = $pay_log->id;
            $pay_log->time_end = $res['time_end'];
            $pay_log->bank_type = $res['bank_type'];
            $pay_log->cash_fee = $res['cash_fee'];
            $pay_log->fee_type = $res['fee_type'];
            $pay_log->is_subscribe = $res['is_subscribe'];
            $pay_log->openid = $res['openid'];
            $pay_log->transaction_id = $res['transaction_id'];
            $pay_log->result_code = $res['result_code'];
            $pay_log->return_code = $res['return_code'];
            $pay_log->save();
            $order->save();

            if ($order_items) {
                if ($order->type == Order::TYPE_BOOK) {
                    $order_items->each(function ($item) {
                        $book = $item->book;
                        $book->student_num += $item->num;
                        $book->save();
                    });
                    //给廖青发送短信通知要给购买书的学员发送教材
                    $user = User::find($order->user_id);
                    if ($user) {
                        $msg = '学员' . $user->receiver_name . ' 电话' . $user->receiver_mobile . '在新媒体网校上购买教材了,请到crm后台查看详细信息!!!';
                        $mobile= 18690421836;
                        Utils::sendSms253($mobile, $msg);
                        SmsRecord::create([
                            'mobile' => $mobile,
                            'send_data' => $msg,
                            'remark' => '新媒体通知教务寄教材',
                            'response_data' => ' '
                        ]);
                    }

                }
            }
            //处理课程归属
            if ($order->type == Order::TYPE_NORMAL) {
                event(new PaymentEvent($order));
            }
        }

        return $this->success($res['trade_state']);
    }
}
