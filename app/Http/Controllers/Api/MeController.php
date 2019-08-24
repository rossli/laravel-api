<?php

namespace App\Http\Controllers\Api;

use App\Models\CourseMember;
use App\Models\Order;
use Illuminate\Http\Request;

class MeController extends BaseController
{
    //
    public function course()
    {
        $course_members = CourseMember::with('course')->where([['user_id', '=', request()->user()->id]])->get();
        $data = [];
        $course_members->each(function ($item) use (&$data) {
            $course = $item->course;
            $data[] = [
                'course_id' => $course['id'],
                'image' => config('jkw.cdn_domain') . '/' . $course['cover'],
                'title' => $course['title'],
            ];
        });
        return $this->success($data);
    }

    public function isStudent($id)
    {
        $course_members = CourseMember::where([['user_id', '=', request()->user()->id], ['course_id', '=', $id]])->first();
        if ($course_members) {
            $data = true;
        } else {
            $data = false;
        }
        return $this->success($data);
    }

    public function order()
    {
        $orders = Order::with([
            'orderItem' => function ($query) {
                $query->select('order_id', 'user_id', 'course_title', 'course_id', 'course_cover');
            },
        ])->where('user_id', request()->user()->id)->orderBy('id', 'DESC')->get()->groupBy('status');
        $data = [];
        $order_item_data = [];
        $sum_order = 0;
        $orders->each(function ($item,$key) use (&$data, &$order_item_data, &$sum_order) {
            $item->each(function ($item) use (&$data, &$order_item_data, &$sum_order,$key) {
                $order_item = $item->orderItem;
                $order_item->each(function ($it) use (&$order_item_data, &$sum_order) {
                    $order_item_data[] = [
                        'num' => $it->num ?: 1,
                        'course_title' => $it->course_title,
                        'course_price' => number_format(($it->course_price/100),2),
                        'course_origin_price' =>  number_format(($it->course_origin_price/100),2),
                        'course_id' => $it->course_id,
                        'course_cover' => config('jkw.cdn_domain') . '/' . $it->course_cover,
                    ];
                    $sum_order += $it->num ?: 1;
                });
                $data[Order::STATUS_NAME[$key]][] = [
                    'order_id' => $item->id,
                    'order_sn' => $item->order_sn,
                    'total_fee' =>number_format(($item->total_fee/100),2),
                    'coupon_deduction' => number_format(($item->coupon_deduction/100),2),
                    'has_paid_fee' =>number_format(($item->has_paid_fee/100),2) ,
                    'status' => $item->status,
                    'type' => $item->type,
                    'paid_at' =>$item->paid_at,
                    'cancel_reason' => $item->cancel_reason,
                    'logistics_number' => $item->logistics_number,
                    'logistics_company' => Order::LOGISTICS[$item->logistics_company],
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'order_item' => $order_item_data,
                    'sum_order' => $sum_order,
                ];
            });
        });
        return $this->success($data);
    }

}
