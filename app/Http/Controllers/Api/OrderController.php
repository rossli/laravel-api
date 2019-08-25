<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
}
