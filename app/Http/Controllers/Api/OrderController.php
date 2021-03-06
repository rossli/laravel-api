<?php

namespace App\Http\Controllers\Api;

use App\Jobs\CancelOrder;
use App\Models\Book;
use App\Models\Course;
use App\Models\GroupGoods;
use App\Models\GroupStudent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PayLog;
use App\Models\ShoppingCart;
use App\Utils\Utils;
use EasyWeChat\Factory;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
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
        $order = Order::with('orderItem')->find($id);
        if ($order) {
            if ($order->user_id != request()->user()->id) {
                return $this->failed('You are wrong! This order_id is not correct !');
            }
            $order_item_data = [];
            $order->orderItem->each(function ($item) use (&$order_item_data) {
                $order_item_data[] = [
                    'num'                 => $item->num,
                    'course_title'        => $item->course_title,
                    'course_price'        => number_format($item->course_price, 2),
                    'course_origin_price' => number_format($item->course_origin_price, 2),
                    'course_id'           => $item->course_id,
                    'course_cover'        => config('jkw.cdn_domain') . '/' . $item->course_cover,
                ];
            });
            $data = [
                'order_id'         => $order->id,
                'order_sn'         => $order->order_sn,
                'total_fee'        => number_format($order->total_fee, 2),
                'coupon_deduction' => number_format($order->coupon_deduction, 2),
                'created_at'       => $order->created_at,
                'updated_at'       => $order->updated_at,
                'order_item'       => $order_item_data,
                'type'             => $order->type,
            ];

            return $this->success($data);
        }

        return $this->failed('当前订单不存在');
    }

    /**
     * 从购物车下单
     * @return mixed
     */
    public function cartSubmit()
    {
        $user = request()->user();
        $carts = ShoppingCart::where('user_id', $user->id)->get();
        if (!$carts) {
            return $this->failed('购物车里没有内容!');
        }
        $from_user_id = $this->getFromUserId(request()->get('from_user_id'));

        $book_price = 0;
        $course_price = 0;
        $str = NULL;
        $promote_fee = 0;
        $carts->each(function ($item) use ($user, &$book_price, &$course_price, &$str, $from_user_id, &$promote_fee) {

            if ($item->type === ShoppingCart::TYPE_BOOK) {
                if (!$this->checkAddress()) {
                    return $this->failed('请添加地址,方便给您发货!', -1);
                }
                $book = Book::find($item->goods_id);
                if ($item->nubmer >= $book->num) {
                    $str = '当前图书库存不足,请联系管理员';

                    return FALSE;
                }
                $book_price += $book->price * $item->number;
                if ($from_user_id && $book->is_promote) {
                    $promote_fee += $book->promote_fee;  //注意: 这里购买多本也按照一本计算分销费用
                }
            } else {
                $course = Course::find($item->goods_id);
                if (!$user->canBuy($item->goods_id)) {
                    $str = '您已购买过此课程!';

                    return FALSE;
                }
                $course_price += $course->price;
                if ($from_user_id && $course->is_promote) {
                    $promote_fee += $course->promote_fee;
                }
            }
        });

        if ($str) {
            return $this->failed($str, -1);
        }

        $sum = $book_price + $course_price;
        $order_sn = Utils::makeSn();

        \DB::beginTransaction();
        try {
            $order = Order::create([
                'order_sn'     => $order_sn,
                'total_fee'    => $sum * 100,
                'wait_pay_fee' => $sum * 100,
                'user_id'      => $user->id,
                'from_user_id' => $from_user_id,
                'promote_fee' => $promote_fee,
            ]);
            $carts->each(static function ($item) use ($user, $order) {
                if ($item->type === ShoppingCart::TYPE_BOOK) {
                    $book = Book::find($item->goods_id);
                    OrderItem::create([
                        'order_id'            => $order->id,
                        'order_sn'            => $order->order_sn,
                        'user_id'             => $user->id,
                        'course_id'           => $book->id,
                        'course_price'        => $book->price * 100,
                        'course_origin_price' => $book->origin_price * 100,
                        'course_title'        => $book->title,
                        'course_cover'        => $book->cover,
                        'num'                 => $item->number,
                        'type'                => $item->type,
                    ]);
                } else {
                    $course = Course::find($item->goods_id);
                    OrderItem::create([
                        'order_id'            => $order->id,
                        'order_sn'            => $order->order_sn,
                        'user_id'             => $user->id,
                        'course_id'           => $course->id,
                        'course_price'        => $course->price * 100,
                        'course_origin_price' => $course->origin_price * 100,
                        'course_title'        => $course->title,
                        'course_cover'        => $course->cover,
                        'num'                 => $item->number,
                        'type'                => $item->type,
                    ]);
                }

                $item->delete();
            });

        } catch (Exception $e) {
            \DB::rollback();

            return $this->failed('订单创建错误,请联系管理员');

        }
        \DB::commit();

        $this->getDelay($order);

        return $this->success($order->id);
    }

    public function groupSubmit(Request $request)
    {
        $goodsable_id = $request->id;
        $group_student_id = $request->group_student_id ?? 0;

        $group_goods = GroupGoods::with('goodsable')
            ->where('goodsable_type', $request->goodsable_type)
            ->where('goodsable_id', $goodsable_id)
            ->enabled()
            ->first();

        if (!$group_goods) {
            return $this->failed('当前课程没有参加团购');
        }

        if ($group_student_id) {
            $group_student = GroupStudent::find($group_student_id);
            if (!$group_student) {
                return $this->failed('此团不存在,请重新建团!');

            }
            if ($group_student->number >= $group_goods->number) {
                return $this->failed('当前团已满,请重新建团');
            }

            $order = Order::where('user_id', Request()->user()->id)
                ->where('group_student_id', $group_student_id)
                ->where('status', Order::STATUS_PAID)
                ->first();

            if ($order) {
                return $this->failed('您已参加过此团购,不能再参加了!');
            }
        }

        $goods = $group_goods->goodsable;

        if ($group_goods->goodsable_type === GroupGoods::GOODS_TYPE_0) {

            if (!Request()->user()->canBuy($group_goods->id)) {
                return $this->failed('您已购买过此课程,不能再次购买!');
            }

            $order_item_type = ShoppingCart::TYPE_COURSE;
        } else {

            if (!$this->checkAddress()) {
                return $this->failed('请添加地址,方便给您发货!', -1);
            }

            if ($goods->num <= 0) {
                return $this->failed('当前图书库存不足,请联系管理员!', -1);
            }
            $order_item_type = ShoppingCart::TYPE_BOOK;
        }

        $from_user_id = $this->getFromUserId($request->get('from_user_id'));

        //订单编号  当前时间(20190909112333)即19年9月9日11点23分33秒 + 时间戳 + user_id
        $order_sn = Utils::makeSn();
        \DB::beginTransaction();
        try {
            $order = Order::create([
                'order_sn'         => $order_sn,
                'total_fee'        => $group_goods->preferential_price * 100,
                'wait_pay_fee'     => $group_goods->preferential_price * 100,
                'user_id'          => $request->user()->id,
                'type'             => Order::TYPE_GROUP,
                'group_student_id' => $group_student_id ?: 0,
                'from_user_id'     => $from_user_id,
            ]);
            OrderItem::create([
                'order_id'            => $order->id,
                'order_sn'            => $order_sn,
                'user_id'             => $request->user()->id,
                'course_id'           => $group_goods->id,
                'course_price'        => $group_goods->preferential_price * 100,
                'course_origin_price' => $goods->price * 100,
                'course_title'        => $goods->title,
                'course_cover'        => $goods->cover,
                'num'                 => 1,
                'type'                => $order_item_type,
            ]);
        } catch (Exception $e) {
            return $this->failed('订单创建错误,请联系管理员', -1);
            \DB::rollback();
        }
        \DB::commit();

        $this->getDelay($order);

        return $this->success([
            'order_id' => $order->id,
        ]);
    }

    public function courseSubmit(Request $request)
    {
        $course_id = $request->get('id');
        $course = Course::find($course_id);

        if (!$request->user()->canBuy($course_id)) {
            return $this->failed('您已购买过此课程!', -1);
        }
        //订单编号  当前时间(20190909112333)即19年9月9日11点23分33秒 + 时间戳
        $order_sn = Utils::makeSn();
        $from_user_id = $this->getFromUserId($request->get('from_user_id'));
        $is_currency = $request->get('is_currency');
        $coupon_deduction = 0;
        if ($is_currency && $course->price > $request->user()->currency) {
            $user = $request->user();
            $coupon_deduction = $user->currency * 100;
            $user->currency = 0;
            $user->save();
        }
        $promote_fee = 0;
        if ($from_user_id && $course->is_promote) {
            $promote_fee += $course->promote_fee;
        }
        \DB::beginTransaction();
        try {
            $order = Order::create([
                'order_sn'         => $order_sn,
                'total_fee'        => $course->price * 100,
                'wait_pay_fee'     => $course->price * 100 - $coupon_deduction,
                'user_id'          => $request->user()->id,
                'type'             => Order::TYPE_NORMAL,
                'from_user_id'     => $from_user_id,
                'coupon_deduction' => $coupon_deduction,
                'promote_fee' => $promote_fee,
            ]);
            OrderItem::create([
                'order_id'            => $order->id,
                'order_sn'            => $order_sn,
                'user_id'             => $request->user()->id,
                'course_id'           => $course->id,
                'course_price'        => $course->price * 100,
                'course_origin_price' => $course->origin_price * 100,
                'course_title'        => $course->title,
                'course_cover'        => $course->cover,
                'num'                 => 1,
                'type'                => ShoppingCart::TYPE_COURSE,
            ]);
        } catch (Exception $e) {
            \DB::rollback();

            return $this->failed('订单创建错误,请联系管理员', -1);
        }
        \DB::commit();
        $this->getDelay($order);

        return $this->success([
            'order_id' => $order->id,
        ]);
    }

    public function bookSubmit(Request $request)
    {
        $book_id = $request->id;
        $book = Book::find($book_id);
        if (!$book) {
            return $this->failed('当前商品不存在,请联系管理员!', -1);
        }
        if (!$this->checkAddress()) {
            return $this->failed('请添加地址,方便给您发货!', -1);
        }

        $num = 1;
        if ($book->num - $num < 0) {
            return $this->failed('库存不足,请联系管理员!', -1);
        }

        $from_user_id = $this->getFromUserId($request->get('from_user_id'));

        $is_currency = $request->is_currency;
        $coupon_deduction = 0;
        if ($is_currency && $book->price > $request->user()->currency) {
            $user = $request->user();
            $coupon_deduction = $user->currency * 100;
            $user->currency = 0;
            $user->save();
        }

        $promote_fee = 0;
        if ($from_user_id && $book->is_promote) {
            $promote_fee += $book->promote_fee;
        }
        $order_sn = Utils::makeSn();
        \DB::beginTransaction();
        try {
            $order = Order::create([
                'order_sn'         => $order_sn,
                'total_fee'        => $book->price * 100,
                'wait_pay_fee'     => $book->price * 100 - $coupon_deduction,
                'user_id'          => $request->user()->id,
                'type'             => Order::TYPE_BOOK,
                'coupon_deduction' => $coupon_deduction,
                'from_user_id'     => $from_user_id,
                'promote_fee' => $promote_fee,
            ]);
            OrderItem::create([
                'order_id'            => $order->id,
                'order_sn'            => $order_sn,
                'user_id'             => $request->user()->id,
                'course_id'           => $book->id,
                'course_price'        => $book->price * 100,
                'course_origin_price' => $book->origin_price,
                'course_title'        => $book->title,
                'course_cover'        => $book->cover,
                'num'                 => $num,
                'type'                => ShoppingCart::TYPE_BOOK,
            ]);
            $book->num--;
            $book->save();

        } catch (Exception $e) {
            \DB::rollback();

            return $this->failed('订单创建错误,请联系管理员', -1);
        }
        \DB::commit();
        $this->getDelay($order);

        return $this->success([
            'order_id' => $order->id,
        ]);
    }

    public function confirm(Request $request)
    {
        $order = Order::with('orderItem')->find($request->id);
        $order_item = [];
        $order->orderItem->each(static function ($item) use (&$order_item, $order) {
            $course_id = $item->course_id;
            if ($order->type == Order::TYPE_GROUP) {
                $group_goods = GroupGoods::find($course_id);
                $course_id = $group_goods->goodsable_id;
            }
            $order_item[] = [
                'cover'        => config('jkw.cdn_domain') . '/' . $item->course_cover,
                'course_price' => $item->course_price,
                'number'       => $item->num,
                'course_id'    => $course_id,
                'course_title' => $item->course_title,
                'type'         => $item->type,
            ];
        });
        $data = [
            'total_fee'        => $order->total_fee,
            'wait_pay_fee'     => $order->wait_pay_fee,
            'type'             => $order->type,
            'coupon_deduction' => $order->coupon_deduction,
            'item'             => $order_item,
            'group_student_id' => $order->group_student_id,
            'status'           => $order->status
        ];

        return $this->success($data);
    }

    private function getPaymentApp()
    : \EasyWeChat\Payment\Application
    {
        $config = [
            // 必要配置
            'app_id'     => config('wechat.payment.default.app_id'),
            'mch_id'     => config('wechat.payment.default.mch_id'),
            'key'        => config('wechat.payment.default.key'),   // API 密钥
            'notify_url' => config('wechat.payment.default.notify_url'),   // API

        ];

        $app = Factory::payment($config);

        return $app;
    }

    public function wxShare()
    {
        $config = [
            'app_id' => config('wechat.official_account.default.app_id'),
            'secret' => config('wechat.official_account.default.secret'),
        ];
        $app = Factory::officialAccount($config);
        $jssdk = $app->jssdk->buildConfig(['updateAppMessageShareData', 'updateTimelineShareData']);

        return $this->success($jssdk);
    }

    private function getOrder($order_id)
    {
        $order = Order::with('orderItem')->where('status', Order::STATUS_WAIT_PAY)->find($order_id);

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

        return $result = Cache::remember($order->order_sn, $minutes,
            function () use ($order, $app, $trade_type, $openid) {

                $total_fee = env('APP_DEBUG') ? 1 : $order->wait_pay_fee * 100;
                $result = $app->order->unify([
                    'trade_type'       => $trade_type,
                    'body'             => '师大教科文-订单支付',
                    'out_trade_no'     => $order->order_sn,
                    'total_fee'        => $total_fee,
                    'spbill_create_ip' => request()->ip(), // 可选，如不传该参数，SDK 将会自动获取相应 IP 地址
                    'openid'           => $openid,
                ]);

                if ($result['result_code'] == 'SUCCESS') {
                    PayLog::create([
                        'order_id' => $order->id,
                        'order_sn' => $order->order_sn,
                        'appid'    => config('wechat.payment.default.app_id'),
                        'mch_id'   => config('wechat.payment.default.mch_id'),

                        'cash_fee'  => $total_fee,
                        'nonce_str' => $result['nonce_str'],

                        'out_trade_no' => $order->order_sn,
                        'result_code'  => $result['result_code'],
                        'return_code'  => $result['return_code'],
                        'sign'         => $result['sign'],
                        'total_fee'    => $order->total_fee * 100,
                        'trade_type'   => $result['trade_type'],
                        'openid'       => $openid ?: '',
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
        if (!$order) {
            return $this->failed('订单错误,请联系管理员', -1);
        }
        $result = $this->unifiy($order, 'JSAPI', $openid);
        if ($result['result_code'] !== 'SUCCESS') {
            return $this->failed('订单错误,请联系管理员', -1);
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
        if (!$order) {
            return $this->failed('订单错误,请联系管理员', -1);
        }
        $result = $this->unifiy($order, 'MWEB');
        if ($result['result_code'] !== 'SUCCESS') {
            return $this->failed('订单错误,请联系管理员', -1);
        }
        info('pay_log:' . json_encode($result));
        $data = [];
        $redirect_url = config('jkw.index_url') . '/m#/order/confirm/' . $order_id . '?status=back';
        $url = $result['mweb_url'] . '&redirect_url=' . urlencode($redirect_url);
        info('mweb_url:' . $url);
        $data['mweb_url'] = $url;

        return $this->success($data);
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

        return $this->failed('参数错误', -1);
    }

    private function getAccessToken($code)
    {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token';

        $param = [
            'appid'      => config('wechat.official_account.default.app_id'),
            'secret'     => config('wechat.official_account.default.secret'),
            'code'       => $code,
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

    public function checkAddress()
    {
        return request()->user()->receiver_mobile && request()->user()->receiver_name && request()->user()->province;
    }

    /**
     * @param Request $request
     *
     * @return int|mixed
     */
    private function getFromUserId($id = NULL)
    {
        $from_user_id = Utils::hashids_decode($id);
        if ($from_user_id) {
            $from_user_id = $from_user_id[0];
        } else {
            $from_user_id = 0;
        }

        return $from_user_id;
    }

    /**
     * @param $order
     */
    private function getDelay($order)
    : void {
        dispatch(new CancelOrder($order->id))->delay(now()->addMinutes(config('jkw.cancel_time')));
    }

}
