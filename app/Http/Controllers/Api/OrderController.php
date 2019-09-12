<?php

namespace App\Http\Controllers\Api;

use App\Jobs\CancelOrder;
use App\Models\Book;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PayLog;
use App\Models\ShoppingCart;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Cache;
use Jenssegers\Agent\Facades\Agent;

class OrderController extends BaseController
{
    //
    public function cancel($id)
    {
        $order = Order::find($id);
        if ($order) {
            $order->status = Order::STATUS_CANCEL;
            $order->save();
            return $this->success('取消成功');
        }
        return $this->failed('当前订单不存在');
    }

    public function show($id)
    {
        $order = Order::with('orderItem')->orderBy('updated_at', 'DESC')->find($id);
        if ($order) {
            $order_item_data = [];
            $order->orderItem->each(function ($item) use (&$order_item_data) {
                $order_item_data[] = [
                    'num' => $item->num,
                    'course_title' => $item->course_title,
                    'course_price' => number_format($item->course_price, 2),
                    'course_origin_price' => number_format($item->course_origin_price, 2),
                    'course_id' => $item->course_id,
                    'course_cover' => config('jkw.cdn_domain') . '/' . $item->course_cover,
                ];
            });
            $data = [
                'order_id' => $order->id,
                'order_sn' => $order->order_sn,
                'total_fee' => number_format($order->total_fee, 2),
                'coupon_deduction' => number_format($order->coupon_deduction, 2),
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'order_item' => $order_item_data,
                'type' => $order->type,
            ];
            return $this->success($data);
        } else {
            return $this->failed('当前订单不存在');
        }
    }

    public function cartSubmit()
    {
        $carts = ShoppingCart::where('user_id', request()->user()->id)->get();
        $book_price = 0;
        $course_price = 0;
        $str = null;
        $carts->each(function ($item) use (&$book_price, &$course_price, &$str) {
            if ($item->type == ShoppingCart::TYPE_BOOK) {
                $book = Book::find($item->goods_id);
                if ($item->nubmer >= $book->num) {
                    $str = '当前图书库存不足,请联系管理员';
                }
                $book_price += $book->price * $item->number;
            } else {
                $course = Course::find($item->goods_id);
                if (!Request()->user()->canBuy($item->goods_id)) {
                    $str = '您已购买过此课程!';
                }
                $course_price += $course->price;
            }
        });
        if ($str) {
            return $this->success($str, -1);
        }
        $sum = $book_price + $course_price;
        $order_sn = date('YmdHis') . (time() + Request()->user()->id);
        \DB::beginTransaction();
        try {
            $order = Order::create([
                'order_sn' => $order_sn,
                'total_fee' => $sum * 100,
                'wait_pay_fee' => $sum * 100,
                'user_id' => Request()->user()->id,
            ]);
            $carts->each(function ($item) use ($order, $order_sn) {
                if ($item->type == ShoppingCart::TYPE_BOOK) {
                    $book = Book::find($item->goods_id);
                    OrderItem::create([
                        'order_id' => $order->id,
                        'order_sn' => $order_sn,
                        'user_id' => Request()->user()->id,
                        'course_id' => $book->id,
                        'course_price' => $book->price * 100,
                        'course_origin_price' => $book->origin_price * 100,
                        'course_title' => $book->title,
                        'course_cover' => $book->cover,
                        'num' => $item->number,
                        'type' => $item->type,
                    ]);
                } else {
                    $course = Course::find($item->goods_id);
                    OrderItem::create([
                        'order_id' => $order->id,
                        'order_sn' => $order_sn,
                        'user_id' => Request()->user()->id,
                        'course_id' => $course->id,
                        'course_price' => $course->price * 100,
                        'course_origin_price' => $course->origin_price * 100,
                        'course_title' => $course->title,
                        'course_cover' => $course->cover,
                        'num' => $item->number,
                        'type' => $item->type,
                    ]);
                }

                $item->delete();
            });

        } catch (Exception $e) {
            \DB::rollback();
            return $this->failed('订单创建错误,请联系管理员');
        }
        \DB::commit();
        $this->dispatch(new CancelOrder($order->id, config('jkw.cancel_time')));
        return $this->success($order->id);
    }

    public function courseSubmit(Request $request)
    {
        $course_id = $request->id;
        $course = Course::find($course_id);
        if (!$request->user()->canBuy($course_id)) {
            return $this->success('您已购买过此课程!', -1);
        }
        //订单编号  当前时间(20190909112333)即19年9月9日11点23分33秒 + 时间戳 + user_id
        $order_sn = date('YmdHis') . (time() + $request->user()->id);
        \DB::beginTransaction();
        try {
            $order = Order::create([
                'order_sn' => $order_sn,
                'total_fee' => $course->price * 100,
                'wait_pay_fee' => $course->price * 100,
                'user_id' => $request->user()->id,
                'type' => Order::TYPE_NORMAL
            ]);
            OrderItem::create([
                'order_id' => $order->id,
                'order_sn' => $order_sn,
                'user_id' => $request->user()->id,
                'course_id' => $course->id,
                'course_price' => $course->price * 100,
                'course_origin_price' => $course->origin_price,
                'course_title' => $course->title,
                'course_cover' => $course->cover,
                'num' => 1,
                'type' => ShoppingCart::TYPE_COURSE
            ]);
        } catch (Exception $e) {
            \DB::rollback();

            return back()->withErrors('订单创建错误,请联系管理员');
        }
        \DB::commit();
        $this->dispatch(new CancelOrder($order->id, config('jkw.cancel_time')));
        return $this->success($order->id);
    }

    public function bookSubmit(Request $request)
    {
        $book_id = $request->id;
        $book = Book::find($book_id);
        if (!$book->num) {
            return $this->success('库存不足,请联系管理员!', -1);
        }
        //订单编号  当前时间(20190909112333)即19年9月9日11点23分33秒 + 时间戳 + user_id
        $order_sn = date('YmdHis') . (time() + $request->user()->id);
        \DB::beginTransaction();
        try {
            $order = Order::create([
                'order_sn' => $order_sn,
                'total_fee' => $book->price * 100,
                'wait_pay_fee' => $book->price * 100,
                'user_id' => $request->user()->id,
                'type' => Order::TYPE_BOOK
            ]);
            OrderItem::create([
                'order_id' => $order->id,
                'order_sn' => $order_sn,
                'user_id' => $request->user()->id,
                'course_id' => $book->id,
                'course_price' => $book->price * 100,
                'course_origin_price' => $book->origin_price,
                'course_title' => $book->title,
                'course_cover' => $book->cover,
                'num' => 1,
                'type' => ShoppingCart::TYPE_BOOK
            ]);

        } catch (Exception $e) {
            \DB::rollback();
            return back()->withErrors('订单创建错误,请联系管理员');
        }
        \DB::commit();
        $this->dispatch(new CancelOrder($order->id, config('jkw.cancel_time')));
        return $this->success($order->id);
    }

    public function confirm(Request $request)
    {
        $order = Order::with('orderItem')->find($request->id);
        $order_item = [];
        $order->orderItem->each(function ($item) use (&$order_item) {
            $order_item[] = [
                'cover' => config('jkw.cdn_domain') . '/' . $item->course_cover,
                'course_price' => $item->course_price,
                'number' => $item->num,
                'course_id' => $item->course_id,
                'course_title' => $item->course_title,
            ];
        });
        $data = [
            'total_fee' => $order->total_fee,
            'wait_pay_fee' => $order->wait_pay_fee,
            'type' => $order->type,
            'coupon_deduction' => $order->coupon_deduction,
            'item' => $order_item
        ];
        return $this->success($data);
    }

    private function getPaymentApp(): \EasyWeChat\Payment\Application
    {
        $config = [
            // 必要配置
            'app_id' => config('wechat.payment.default.app_id'),
            'mch_id' => config('wechat.payment.default.mch_id'),
            'key' => config('wechat.payment.default.key'),   // API 密钥
            'notify_url' => config('wechat.payment.default.notify_url'),   // API

        ];

        $app = Factory::payment($config);
        return $app;
    }

    private function getOrder($order_id)
    {
        $order = Order::with('orderItem')->where('status', Order::STATUS_WAIT_PAY)->findOrFail($order_id);

        return $order;
    }

    private function unifiy($order, $trade_type, $openid = NULL)
    {

        //支付二维码120分钟失效
        $minutes = 120;
        if (env('APP_DEBUG')) {
            Cache::forget($order->order_sn);
        }
        $app = $this->getPaymentApp();
        return $result = Cache::remember($order->order_sn, $minutes, function () use ($order, $app, $trade_type, $openid) {

            $total_fee = env('APP_DEBUG') ? 1 : $order->wait_pay_fee * 100;
            $result = $app->order->unify([
                'trade_type' => $trade_type,
                'body' => '师大教科文-订单支付',
                'out_trade_no' => $order->order_sn,
                'total_fee' => $total_fee,
                'spbill_create_ip' => request()->ip(), // 可选，如不传该参数，SDK 将会自动获取相应 IP 地址
                'openid' => $openid,
            ]);

            if ($result['result_code'] == 'SUCCESS') {
                PayLog::create([
                    'order_id' => $order->id,
                    'order_sn' => $order->order_sn,
                    'appid' => config('wechat.payment.default.app_id'),
                    'mch_id' => config('wechat.payment.default.mch_id'),

                    'cash_fee' => $total_fee,
                    'nonce_str' => $result['nonce_str'],

                    'out_trade_no' => $order->order_sn,
                    'result_code' => $result['result_code'],
                    'return_code' => $result['return_code'],
                    'sign' => $result['sign'],
                    'total_fee' => $order->total_fee * 100,
                    'trade_type' => $result['trade_type'],
                    'openid' => $openid ?: '',
                ]);

            }
            return $result;
        });

    }

    public function paymentWx()
    {
        $openid = request('openid');
        $app = $this->getPaymentApp();
        $order_id = request('id');
        $order = $this->getOrder($order_id);
        $result = $this->unifiy($order, 'JSAPI', $openid);
        if ($result['result_code'] !== 'SUCCESS') {
            return back()->withErrors('订单错误,请联系管理员');
        }
        $jssdk = $app->jssdk;
        $config = $jssdk->bridgeConfig($result['prepay_id']);

        $data['config'] = $config;

        return $this->success($data);
    }

    public function paymentH5()
    {
        $order_id = request('id');
        $order = $this->getOrder($order_id);
        $result = $this->unifiy($order, 'MWEB');
        if ($result['result_code'] !== 'SUCCESS') {
            return back()->withErrors('订单错误,请联系管理员');
        }
        info('pay_log:' . json_encode($result));
        $data = [];

        $redirect_url = config('jkw.index_url') . '/m#/order/confirm/' . $order_id . '?status=back';
        $url = $result['mweb_url'] . '&redirect_url=' . urlencode($redirect_url);
        info('mweb_url:' . $url);
        $data['mweb_url'] = $url;

        return $this->success($data);
    }

    public function confirmH5()
    {
        $order_id = request('id');
        return view('web.order.confirm-h5', compact('order_id'));
    }

    public function getOpenid()
    {
        $code = request('code', '');
        $res = $this->getAccessToken($code);

        if (!isset($res->errcode)) {
            return $this->success([
                'openid' => $res->openid,
            ]);
        }

        return $this->failed('参数错误');
    }

    private function getAccessToken($code)
    {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token';

        $param = [
            'appid' => config('wechat.official_account.default.app_id'),
            'secret' => config('wechat.official_account.default.secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];

        $request_url = $url . '?' . http_build_query($param);

        $body = $this->requestGet($request_url);
        return $body;
    }

    /**
     * @param string $request_url
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
     */
    private function requestGet(string $request_url)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $request_url);
        $body = json_decode($response->getBody());

        return $body;
    }


}
