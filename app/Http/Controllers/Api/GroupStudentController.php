<?php

namespace App\Http\Controllers\Api;

use App\Models\GroupStudent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GroupStudentController extends BaseController
{
    //
    public function index()
    {
        $group_student = GroupStudent::with('group_goods', 'order', 'order.order_item')->where('user_id', request()->user()->id)->get();
        if (!$group_student) {
            return $this->failed('信息错误,请联系管理员', -1);
        }
        $data = [];
        $group_student->each(function ($item) use (&$data) {
            $order = $item->order;
            $order->each(function ($it) use (&$data, $item) {
                $order_item = $it->order_item;
                $data[] = [
                    'status' => $item->status,
                    'type' => $order_item->type,
                    'course_price' => $order_item->course_price,
                    'course_origin_price' => $order_item->course_origin_price,
                    'course_title' => $order_item->course_title,
                    'course_cover' => config('jkw.cdn_domain') . '/' . $order_item->course_cover,
                    'course_id' => $order_item->course_id,
                ];
            });
        });
        return $this->success($data);
    }



}
