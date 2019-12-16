<?php

namespace App\Http\Controllers\Api;

use App\Models\Book;
use App\Models\GroupGoods;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BookController extends BaseController
{
    //
    public function index()
    {
        $books = Book::where('enabled', 1)->limit(4)->orderBy('updated_at', 'DESC')->get();
        $data = [];
        $books->each(function ($item) use (&$data) {
            $data[] = [
                'image' => config('jkw.cdn_domain') . '/' . $item->cover,
                'title' => $item->title,
                'id' => $item->id,
                'price' => $item->price,
                'student_num' => $item->student_num + $item->student_add,
                'is_activity'=>$item->is_activity,
            ];
        });
        return $this->success($data);
    }

    public function show(Request $request)
    {
        $book = Book::where('enabled', 1)->find($request->id);
        if ($book->is_group) {
            $group_goods = GroupGoods::where('goodsable_type', GroupGoods::GOODS_TYPE_1)
                ->enabled()
                ->where('goodsable_id', $request->id)->first();
            if ($group_goods) {
                $book->is_group = true;
            } else {
                $book->is_group = false;
            }
        }

        $data = [
            'image' => config('jkw.cdn_domain') . '/' . $book->cover,
            'title' => $book->title,
            'id' => $book->id,
            'price' => $book->price,
            'subtitle' => $book->subtitle,
            'origin_price' => $book->origin_price,
            'summary' => $book->summary,
            'menu' => $book->menu,
            'num' => $book->num,
            'student_num' => $book->student_num + $book->student_add,
            'is_group' => $book->is_group,
            'is_activity'=>$book->is_activity,
            'is_currency'=>$book->is_currency
        ];
        return $this->success($data);
    }

    public function list()
    {
        $books = Book::where('enabled', 1)->orderBy('updated_at', 'DESC')->get();
        $data = [];
        $books->each(function ($item) use (&$data) {
            $data[] = [
                'image' => config('jkw.cdn_domain') . '/' . $item->cover,
                'title' => $item->title,
                'subtitle' => $item->subtitle,
                'id' => $item->id,
                'price' => $item->price,
                'origin_price' => $item->origin_price,
                'student_sum' => $item->student_num + $item->student_add,
                'is_activity'=>$item->is_activity,
            ];
        });
        return $this->success($data);
    }
}
