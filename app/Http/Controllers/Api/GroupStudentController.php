<?php

namespace App\Http\Controllers\Api;

use App\Models\GroupStudent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GroupStudentController extends BaseController
{
    //

    public function share(Request $request)
    {

        $group_student_id = $request->id;
        $group_student = GroupStudent::with('groupGoods', 'groupGoods.goodsable', 'order', 'order.user')->find($group_student_id);

        if (!$group_student) {
            return $this->failed('数据错误!');

        }
        $order = $group_student->order;

        $groupGoods = $group_student->groupGoods;
        $enabled = 0;
        if ($groupGoods->enabled &&
            $group_student->groupGoods->enabled &&
            $groupGoods->start_time <= now() &&
            $groupGoods->start_time > now() &&
            $group_student->groupGoods->is_group &&
            $group_student->groupGoods->price) {
            $enabled = 1;
        }
        $data = [
            'type'=>$groupGoods->goodsable_type,
            'goods_id' => $groupGoods->goodsable->id,
            'title' => $groupGoods->goodsable->title,
            'subtitle' => $groupGoods->goodsable->subtitle,
            'img' => config('jkw.cdn_domain') . '/' . $groupGoods->goodsable->cover,
            'enabled' => $enabled,
            'price' => $groupGoods->goodsable->price,
            'number' => $groupGoods->number,
            'time' => (strtotime("+1 day", strtotime($group_student->created_at)) - time()) * 1000,
            'start_time' => date('Y-m-d', strtotime($groupGoods->start_time)),
            'end_time' => date('Y-m-d', strtotime($groupGoods->end_time)),
            'preferential_price' => $groupGoods->preferential_price,
            'people' => $order->count(),
            'is_finish' => $group_student->status == GroupStudent::STATUS_FINISHED || $groupGoods->number == $order->count() || (strtotime("+1 day", strtotime($group_student->created_at)) - time()) <= 0,
        ];
        $order->each(function ($item) use (&$data) {
            $user = $item->user;
            if ($user) {
                $data['user'][] = [
                    'nick_name' => $user->nick_name,
                    'avatar' => $user->avatar ? config('jkw.cdn_domain') . '/' . $user->avatar : config('jkw.cdn_domain') . '/' . config('jkw.default_avatar'),
                ];
            }
        });

        return $this->success($data);
    }


}
