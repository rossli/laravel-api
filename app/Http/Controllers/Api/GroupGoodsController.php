<?php

namespace App\Http\Controllers\Api;

use App\Models\Course;
use App\Models\GroupGoods;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;

class GroupGoodsController extends BaseController
{
    //列表
    public function list()
    {
        $group_goods = GroupGoods::with('course', 'book')
            ->where('start_time', '<', now())
            ->where('end_time', '>', now())
            ->where('enabled', 1)->get();
        $data = [];
        if ($group_goods) {
            $group_goods->each(function ($item) use (&$data) {
                $model = [
                    'goodsable_id' => $item->goodsable_id,
                    'preferential_price' => $item->preferential_price,
                    'student_sum' => $item->student_add + $item->student_num,
                ];
                if ($item->goodsable_type == GroupGoods::GOODS_TYPE_1) {
                    $books = $item->book;
                    $goods = [
                        'goodsable_type' => 'book',
                        'image' => config('jkw.cdn_domain') . '/' . $books->cover,
                        'title' => $books->title,
                        'subtitle' => $books->subtitle,
                    ];
                } else {
                    $course = $item->course;
                    $goods = [
                        'goodsable_type' => 'course',
                        'image' => config('jkw.cdn_domain') . '/' . $course->cover,
                        'title' => $course->title,
                        'subtitle' => $course->subtitle,
                    ];
                }
                $data[] = array_merge($model, $goods);
            });
        }

        return $this->success($data);

    }

    public function show(Request $request)
    {
        $goodsable_id = $request->goodsable_id ?: 1;
        $goodsable_type = $request->goodsable_type ?: GroupGoods::GOODS_TYPE_1;
        $group_goods = GroupGoods::where('goodsable_id', $goodsable_id)->where('goodsable_type', $goodsable_type)->where('enabled', 1)->first();
        if (!$group_goods) {
            return $this->failed('不存在该课程,请联系管理员!', -1, 'failed');
        }
        $data = [
            'number' => $group_goods->number,
            'preferential_price' => $group_goods->preferential_price,
            'student_sum' => $group_goods->student_add + $group_goods->student_num,
            'end_time' => strtotime($group_goods->end_time) - time(),
        ];
        return $this->success($data);
    }

    public function confirm(Request $request)
    {
        $goodsable_id = $request->id;
        $group_goods = GroupGoods::with('goodsable')->where('goodsable_id', $goodsable_id)->first();
        $goodsable = $group_goods->goodsable;
        if ($group_goods) {
            $data = [
                'cover' => config('jkw.cdn_domain') . '/' . $goodsable->cover,
                'title' => $goodsable->title,
                'subtitle' => $goodsable->subtitle,
                'goodsable_id' => $group_goods->id,
                'goodsable_type' => $group_goods->goodsable_type,
                'preferential_price' => $group_goods->preferential_price,
                'student_sum' => $group_goods->student_add + $group_goods->student_num,
            ];
            return $this->success($data);
        }
        return $this->failed('不存在当前课程,请联系管理员', -1);

    }
}
