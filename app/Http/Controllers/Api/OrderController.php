<?php

namespace App\Http\Controllers\Api;

use App\Models\Book;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShoppingCart;
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

    public function cartSubmit()
    {
        $goods = ShoppingCart::where(['user_id', '=', request()->user()->id])->get();
        $book_price = 0;
        $course_price = 0;
        $goods->each(function ($item) use (&$book_price, &$course_price) {
            if ($item->type == ShoppingCart::TYPE_BOOK) {
                $book = Book::find($item->goods_id);
                if ($item->nubmer >= $book->num) {
                    return $this->success('当前图书库存不足,请联系管理员', -1);
                }
                $book_price += $book->price;
                OrderItem::create([
                    'course_id' => $book->id,
                    'course_price' => $book->price * 100,
                    'course_origin_price' => $book->origin_price,
                    'course_title' => $book->title,
                    'course_cover' => $book->cover,
                    'num' => $item->nubmer,
                ]);

            } else {
                $course = Course::find($item->goods_id);
                if (Request()->user()->canBuy($item->goods_id)) {
                    return $this->success('您已购买过此课程!', -1);
                }
                $course_price += $course->price;
                OrderItem::create([
                    'course_id' => $course->id,
                    'course_price' => $course->price * 100,
                    'course_origin_price' => $course->origin_price,
                    'course_title' => $course->title,
                    'course_cover' => $course->cover,
                    'num' => $item->nubmer,
                ]);
            }
        });
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
            OrderItem::update([
                'order_id' => $order->id,
                'order_sn' => $order_sn,
                'user_id' => Request()->user()->id,
            ]);
        } catch (Exception $e) {
            \DB::rollback();
            return back()->withErrors('订单创建错误,请联系管理员');
        }
        \DB::commit();
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
            ]);
        } catch (Exception $e) {
            \DB::rollback();

            return back()->withErrors('订单创建错误,请联系管理员');
        }
        \DB::commit();
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
            ]);
        } catch (Exception $e) {
            \DB::rollback();

            return back()->withErrors('订单创建错误,请联系管理员');
        }
        \DB::commit();
        return $this->success($order->id);
    }

    public function confirm(Request $request)
    {
        $order = Order::with('orderItem')->find($request->id);
        $order_item = [];
        $order->orderItem->each(function ($item) use (&$order_item) {
            $order_item = [
                'cover' => config('jkw.cdn_domain') . '/' . $item->course_cover,
                'course_price' => $item->course_price,
                'num' => $item->num,
                'course_id' => $item->course_id,
                'course_title' => $item->course_title,
            ];
        });
        $data = [
            'total_fee' => $order->total_fee,
            'wait_pay_fee' => $order->wait_pay_fee,
            'coupon_deduction' => $order->coupon_deduction,
            'item' => $order_item
        ];
        return $this->success($data);
    }
}
