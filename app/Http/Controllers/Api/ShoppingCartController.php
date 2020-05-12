<?php

namespace App\Http\Controllers\Api;

use App\Models\ShoppingCart;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ShoppingCartController extends BaseController
{
    //
    public function index()
    {
        $carts = ShoppingCart::with('book', 'course')->where('user_id', request()->user()->id)->get();
        $data = [];
        $goods = [];

        if ($carts) {
            $carts->each(function ($item) use (&$data, &$goods) {
                if ($item->type == ShoppingCart::TYPE_BOOK) {
                    $goods = [
                        'cover' => config('jkw.cdn_domain') . '/' . $item->book['cover'],
                        'title' => $item->book['title'],
                        'price' => $item->book['price'],
                        'subtitle' => $item->book['subtitle'],
                    ];
                } else {
                    $goods = [
                        'cover' => config('jkw.cdn_domain') . '/' . $item->course['cover'],
                        'title' => $item->course['title'],
                        'price' => $item->course['price'],
                        'subtitle' => $item->course['subtitle'],
                    ];
                }
                $data[] = [
                    'goods_id' => $item->goods_id,
                    'number' => $item->number,
                    'type' => $item->type,
                    'goods' => $goods
                ];
            });
            return $this->success($data);
        }
        return $this->failed('数据错误');
    }

    public function store(Request $request)
    {
        if ($request->user()->id) {
            $goods = ShoppingCart::where([['user_id', '=', $request->user()->id], ['type', '=', $request->type], ['goods_id', '=', $request->goods_id]])->first();
            if ($goods) {
                if ($request->type) {
                    return $this->success('您购物车已有改商品了!', -1);
                } else if ($goods->number >= 5) {
                    return $this->success('为保证产品质量,单个商品最多5件!', -1);
                } else {
                    $goods->number++;
                    $goods->save();
                    return $this->success('成功加入购物车');
                }
            }
            ShoppingCart::create([
                'user_id' => $request->user()->id,
                'type' => $request->type,
                'goods_id' => $request->goods_id,
                'number' => 1,
            ]);
            return $this->success('成功加入购物车');
        }
        return $this->failed('请登录!');
    }

    public function count()
    {
        $cart = ShoppingCart::where('user_id', request()->user()->id)->get();
        if($cart){
            $data=[
                'count'=>count($cart)
            ];
            return $this->success($data);
        }
        return $this->failed('没有数据!');

    }

    public function delete(Request $request)
    {
        ShoppingCart::where('goods_id', $request->get('id'))->where('user_id', $request->user()->id)->delete();
        return $this->success('删除成功');
    }
}
