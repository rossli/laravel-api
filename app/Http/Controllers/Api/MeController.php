<?php

namespace App\Http\Controllers\Api;

use App\Models\CourseMember;
use App\Models\GroupGoods;
use App\Models\GroupStudent;
use App\Models\Order;
use Illuminate\Http\Request;

class MeController extends BaseController
{

    //
    public function course()
    {
        $course_members = CourseMember::with(['course' => function ($query) {
            $query->where('enabled', 1);
        }])->where([['user_id', '=', request()->user()->id]])->orderBy('id','DESC')->get();
        $data = [];
        $course_members->each(function ($item) use (&$data) {
            if ($item->course) {
                $data[] = [
                    'course_id' => $item->course_id,
                    'image' => config('jkw.cdn_domain') . '/' . $item->course->cover,
                    'title' => $item->course->title,
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
                    'num' => $it->num,
                    'course_title' => $it->course_title,
                    'course_price' => number_format($it->course_price, 2),
                    'course_origin_price' => number_format($it->course_origin_price, 2),
                    'course_id' => $it->course_id,
                    'course_cover' => config('jkw.cdn_domain') . '/' . $it->course_cover,
                ];
            });
            $data[] = [
                'order_id' => $item->id,
                'order_sn' => $item->order_sn,
                'total_fee' => number_format($item->total_fee, 2),
                'coupon_deduction' => number_format($item->coupon_deduction, 2),
                'has_paid_fee' => number_format($item->has_paid_fee, 2),
                'status' => Order::STATUS_NAME[$item->status],
                'type' => $item->type,
                'paid_at' => $item->paid_at,
                'cancel_reason' => $item->cancel_reason,
                'logistics_number' => $item->logistics_number,
                'logistics_company' => Order::LOGISTICS[$item->logistics_company],
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'order_item' => $order_item_data,
                'sum_order' => $order_sum,
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
            }, 'groupStudent', 'groupStudent.groupGoods'
        ])->where('user_id', request()->user()->id)
            ->where('type', Order::TYPE_GROUP)
            ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_FINISHED, Order::STATUS_DISPATCH])
            ->orderBy('id', 'DESC')->get();
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
                    'num' => $it->num,
                    'course_title' => $it->course_title,
                    'course_price' => number_format($it->course_price, 2),
                    'course_origin_price' => number_format($it->course_origin_price, 2),
                    'course_id' => $group_goods->goodsable_id,
                    'course_cover' => config('jkw.cdn_domain') . '/' . $it->course_cover,
                ];
            });
            $data[] = [
                'order_id' => $item->id,
                'order_sn' => $item->order_sn,
                'total_fee' => number_format($item->total_fee, 2),
                'coupon_deduction' => number_format($item->coupon_deduction, 2),
                'status' => GroupStudent::STATUS[$group_student->status],
                'type' => $group_student->groupGoods->goodsable_type,
                'paid_at' => $item->paid_at,
                'cancel_reason' => $item->cancel_reason,
                'logistics_number' => $item->logistics_number,
                'logistics_company' => Order::LOGISTICS[$item->logistics_company],
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'order_item' => $order_item_data,
                'num' => $group_student->number,
                'group_student_id' => $group_student->id,

            ];
        });

        return $this->success($data);
    }
}
