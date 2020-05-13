<?php

namespace App\Http\Controllers\Api;

use App\Models\AccountRecord;
use App\Models\AdminUser;
use App\Models\CourseMember;
use App\Models\GroupGoods;
use App\Models\GroupStudent;
use App\Models\Order;
use App\Models\User;
use App\Utils\Utils;
use EasyWeChat\Factory;
use Illuminate\Http\Request;

class MeController extends BaseController
{

    //
    public function course()
    {
        $course_members = CourseMember::with([
            'course' => function ($query) {
                $query->where('enabled', 1);
            },
        ])->where([['user_id', '=', request()->user()->id]])->orderBy('id', 'DESC')->get();

        $data = [];
        $course_members->each(function ($item) use (&$data) {
            if ($item->course) {
                $data[] = [
                    'course_id' => $item->course_id,
                    'image'     => config('jkw.cdn_domain') . '/' . $item->course->cover,
                    'title'     => $item->course->title,
                ];
            }

        });

        return $this->success($data);
    }

    public function isStudent($id)
    {
        $course_members = CourseMember::where([['user_id', '=', request()->user()->id], ['course_id', '=', $id]])
            ->first();
        if ($course_members) {
            $data['is_student'] = TRUE;
        } else {
            $data['is_student'] = FALSE;
        }

        return $this->success($data);
    }

    public function order()
    {
        $orders = Order::with([
            'orderItem' => function ($query) {
                $query->select('order_id', 'user_id', 'course_origin_price', 'course_price', 'course_title',
                    'course_id', 'course_cover', 'num');
            },
        ])->where('user_id', request()->user()->id)->orderBy('id', 'DESC')->get();
        $data = [];
        $order_sum = 0;
        $orders->each(function ($item) use ($order_sum, &$data) {
            $order_item_data = [];
            $item->orderItem->each(function ($it) use (&$order_item_data, &$order_sum) {
                $order_sum += $it->num;
                $order_item_data[] = [
                    'num'                 => $it->num,
                    'course_title'        => $it->course_title,
                    'course_price'        => number_format($it->course_price, 2),
                    'course_origin_price' => number_format($it->course_origin_price, 2),
                    'course_id'           => $it->course_id,
                    'course_cover'        => config('jkw.cdn_domain') . '/' . $it->course_cover,
                    'type'                =>$it->type
                ];
            });
            $data[] = [
                'order_id'          => $item->id,
                'order_sn'          => $item->order_sn,
                'total_fee'         => number_format($item->total_fee, 2),
                'coupon_deduction'  => number_format($item->coupon_deduction, 2),
                'has_paid_fee'      => number_format($item->has_paid_fee, 2),
                'status'            => Order::STATUS_NAME[ $item->status ],
                'type'              => $item->type,
                'paid_at'           => $item->paid_at,
                'cancel_reason'     => $item->cancel_reason,
                'logistics_number'  => $item->logistics_number,
                'logistics_company' => Order::LOGISTICS[ $item->logistics_company ],
                'created_at'        => $item->created_at,
                'updated_at'        => $item->updated_at,
                'order_item'        => $order_item_data,
                'sum_order'         => $order_sum,
            ];
        });

        return $this->success($data);
    }

    public function group()
    {
        $orders = Order::with([
            'orderItem' => function ($query) {
                $query->select('order_id', 'user_id', 'course_origin_price', 'course_price', 'course_title',
                    'course_id', 'course_cover', 'num');
            },
            'groupStudent',
            'groupStudent.groupGoods',
        ])
            ->where('user_id', request()->user()->id)
            ->where('type', Order::TYPE_GROUP)
            ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_FINISHED, Order::STATUS_DISPATCH])
            ->orderBy('id', 'DESC')
            ->get();
        $data = [];
        $order_sum = 0;
        $orders->each(function ($item) use ($order_sum, &$data) {
            $group_student = $item->groupStudent;
            if (!$group_student) {
                return $this->failed('数据错误,请联系管理员!', -1);
            }

            $order_item_data = [];
            $item->orderItem->each(function ($it) use (&$order_item_data, &$order_sum) {
                $group_goods = GroupGoods::find($it->course_id);
                $order_sum += $it->num;
                $order_item_data[] = [
                    'num'                 => $it->num,
                    'course_title'        => $it->course_title,
                    'course_price'        => number_format($it->course_price, 2),
                    'course_origin_price' => number_format($it->course_origin_price, 2),
                    'course_id'           => $group_goods->goodsable_id,
                    'course_cover'        => config('jkw.cdn_domain') . '/' . $it->course_cover,
                ];
            });
            $data[] = [
                'order_id'          => $item->id,
                'order_sn'          => $item->order_sn,
                'total_fee'         => number_format($item->total_fee, 2),
                'coupon_deduction'  => number_format($item->coupon_deduction, 2),
                'status'            => GroupStudent::STATUS[ $group_student->status ],
                'type'              => $group_student->groupGoods->goodsable_type,
                'paid_at'           => $item->paid_at,
                'cancel_reason'     => $item->cancel_reason,
                'logistics_number'  => $item->logistics_number,
                'logistics_company' => Order::LOGISTICS[ $item->logistics_company ],
                'created_at'        => $item->created_at,
                'updated_at'        => $item->updated_at,
                'order_item'        => $order_item_data,
                'num'               => $group_student->number,
                'group_student_id'  => $group_student->id,

            ];
        });

        return $this->success($data);
    }

    public function fromUser()
    {
        $user = User::where('from_user_id', request()->user()->id)->get();

        $data = [];
        if ($user) {
            $user->each(function ($item) use (&$data) {
                $data[] = [
                    'avatar'     => config('jkw.cdn_domain') . '/' . $item->avatar,
                    'nick_name'  => $item->nick_name,
                    'created_at' => date_format($item->created_at, 'Y-m-d H:i:s'),
                ];
            });
        }

        return $this->success($data);
    }

    public function currency()
    {
        return $this->success(request()->user()->currency);
    }

    public function code()
    {
        return $this->success(request()->user()->getHashCode());
    }

    public function account()
    {
        $user = request()->user();
        if (!$user->is_promoter) {
            return $this->failed('您还不是推广合伙人,快快加入吧!');
        }

        $data = [
            'user_id' => $user->id,
            'nick_name' => $user->nick_name,
            'avatar' => $user->avatar ? config('jkw.cdn_domain') . '/' . $user->avatar : config('jkw.cdn_domain') . '/' . config('jkw.default_avatar'),
            'promote_fee' => $user->promote_fee,   //累计收益
            'can_withdraw' => $user->can_withdraw,  //可提现
            'withdrawn' => $user->withdrawn,   //已提现
            'tobe_confirm' => $user->tobe_confirm,  //待确认
            'invite_count' => $user->getInviteCount(),
            'invite_order_list' => $user->getInviteOrderList(),
        ];

        return $this->success($data);
    }

    public function joinPromote()
    {
        $join = (int) request()->get('join', 0) ? 1 : 0;

        $user = request()->user();
        $user->is_promoter = $join;
        $user->join_at = now();
        $user->save();

        return $this->success();

    }

    public function accountRecord()
    {
        $type = request()->get('type', 0);
        $data = [];

        AccountRecord::where('user_id', request()->user()->id)->where('type', $type)->get()->each(static function ($item
        ) use (&$data) {
            $data[] = [
                'type'       => $item->type,
                'type_name'  => $item->getTypeName(),
                'money'      => $item->money,
                'created_at' => $item->created_at->toDateTimeString(),
            ];
        });

        return $this->success($data);
    }

    public function promoteOrders()
    {
        $data = request()->user()->getInviteOrderList();

        return $this->success($data);
    }

    /**
     * 发放付款红包
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function withdraw()
    {
        $admin_user = $this->getCompanyUser();
        $user = request()->user();
        $amount = request()->get('amount', 0);

        if ($admin_user->account < $amount) {
            return $this->failed('企业账户余额不足,请联系管理员或者客服人员');
        }
        if ($admin_user->daily_withdraw > config('jkw.withdraw_amount_daily_limit')) {
            return $this->failed('已达到企业账户日提现限额,请明日再来');
        }

        if ($amount < $minimum_amount = config('jkw.withdraw_amount')) {
            return $this->failed('提现金额不能少于' . $minimum_amount . '元');
        }
        if ($user->can_withdraw < $amount) {
            return $this->failed('您的可提现金额不足');
        }

        if (!$user->is_promoter) {
            return $this->failed('您还不是我们的分销人员哦,快快加入吧');
        }

        $openid = request()->get('openid', '');
        if (!$openid || !$this->checkSubscribe($openid)) {
            return $this->failed('请先关注公众号后再来提现哦');
        }

        if (env('APP_DEBUG')) {
            $minimum_amount = 1;
            $openid = 'o_ysnwFHdBWTZ0gmeaAFx6aRh_10';
        }

        $config = config('wechat.payment.default');
        $payment = Factory::payment($config);
        $redpack = $payment->redpack;
        $sn = Utils::makeSn('wd');  //withdraw
        $redpackData = [
            'mch_billno'   => $sn,
            'send_name'    => '师大教科文提现红包',
            're_openid'    => $openid,
            'total_num'    => 1,  //固定为1，可不传
            'total_amount' => $minimum_amount * 100,  //单位为分，不小于100
            'wishing'      => '继续加油哦',
            'act_name'     => '提现红包',
            'remark'       => '邀请越多奖励越多',
            // ...
        ];
        $result = $redpack->sendNormal($redpackData);
//        $result = '{
//  "send_listid" : "1000041701202005123002719191440",
//  "err_code" : "SUCCESS",
//  "re_openid" : "o_ysnwMa8RsUaaQkk-HdftfXa7p0",
//  "total_amount" : "100",
//  "err_code_des" : "发放成功",
//  "return_msg" : "发放成功",
//  "mch_billno" : "wd202005121803551589277835",
//  "return_code" : "SUCCESS",
//  "wxappid" : "wxeb99f78727420b07",
//  "mch_id" : "1448506702",
//  "result_code" : "SUCCESS"
//}';
//        $result = json_decode($result,1);

        if ($result['return_code'] === 'SUCCESS') {
            info('redpack', $result);
            if ($result['result_code'] === 'SUCCESS') {

                $this->handleUser($amount, $user);

                $res = $this->handleData($amount, $user, $sn, $admin_user);
                if ($res) {
                    info('提现成功');
                    return $this->success('提现成功');
                }

                return $this->failed('提现失败');
            }

            if ($result['err_code'] === 'SYSTEMERROR') {
                $mchBillNo = $sn;
                $res = $redpack->info($mchBillNo);
                if ($res['result_code'] === 'SUCCESS') {
                    $this->handleUser($amount, $user);
                    $res = $this->handleData($amount, $user, $sn, $admin_user);
                    if ($res) {
                        return $this->success('提现成功');
                    }

                    return $this->failed('提现失败');
                }
            }
            return $this->failed($result['return_msg']);
        }

        return $this->failed('通信错误');
    }

    protected function getCompanyUser()
    {
        return AdminUser::find(1);
    }

    /**
     * 是否关注 公众号
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function isSubscribe()
    {
        $user = request()->user();
        $config = config('wechat.official_account.default');
        $app = Factory::officialAccount($config);
        $openid = $user->openid;
        if (!$openid) {
            return $this->success([
                'is_subscribe' => 0,
            ]);
        }

        return $this->success([
            'is_subscribe' => $this->checkSubscribe($openid),
        ]);
    }

    /**
     * @param $openid
     *
     * @return bool
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    private function checkSubscribe($openid)
    : bool {
        $official_config = config('wechat.official_account.default');
        $official_app = Factory::officialAccount($official_config);
        $wechat_user = $official_app->user->get($openid);
        if (isset($wechat_user['subscribe'])) {
            return $wechat_user['subscribe'];
        }

        return 0;
    }

    /**
     * @param        $amount
     * @param        $user
     * @param string $sn
     * @param        $admin_user
     *
     * @return mixed
     */
    private function handleData($amount, $user, string $sn, $admin_user)
     {
        \DB::beginTransaction();
        try {

            info('start');
            AccountRecord::create([
                'user_id' => $user->id,
                'sn'      => $sn,
                'status'  => 'SUCCESS',
                'type'    => AccountRecord::TYPE_2,
                'money'   => $amount,
            ]);

            $admin_user->increment('total_withdraw_times');
            $admin_user->daily_withdraw += $amount;
            $admin_user->total_withdraw += $amount;
            $admin_user->save();
            info('finish');

        } catch (\Exception $e) {
            \DB::rollback();
            info($e->getMessage());

            return FALSE;
        }
        \DB::commit();
         return TRUE;
    }

    /**
     * @param $amount
     * @param $user
     */
    private function handleUser($amount, $user)
    : void {
        $user->can_withdraw -= $amount;
        $user->withdrawn += $amount;
        $user->increment('withdraw_times');
        $user->save();
    }
}
