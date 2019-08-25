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
        $carts = ShoppingCart::where('user_id', request()->user()->id)->get();
        $data = [];
        if ($carts) {
            $carts->each(function ($item) {
                $data[] = [
                    'goods_id' => $item->goods_id,
                    'number' => $item->number,
                    'type' => $item->type
                ];
            });
            return $this->success($data);
        }
        return $this->failed('数据错误');
    }

    public function store(Request $request)
    {
        if ($request->user()->id) {
            ShoppingCart::create([
                'user_id' => $request->user()->id,
                'type' => $request->type,
                'goods_id' => $request->goods_id,
                'number' => $request->number ?: 1,
            ]);
            return $this->success('成功加入购物车');
        }
        return $this->failed('请登录!');
    }

    public function count()
    {
        $count=ShoppingCart::where('user_id',request()->user()->id)->count('number');
        if($count){
            return $this->success($count);
        }
        return $this->failed('数据错误!');
    }

    public function delete()
    {

    }
}
