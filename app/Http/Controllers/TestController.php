<?php

namespace App\Http\Controllers;

use App\Models\GroupGoods;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $result = '{
  "send_listid" : "1000041701202005123002719191440",
  "err_code" : "SUCCESS",
  "re_openid" : "o_ysnwMa8RsUaaQkk-HdftfXa7p0",
  "total_amount" : "100",
  "err_code_des" : "发放成功",
  "return_msg" : "发放成功",
  "mch_billno" : "wd202005121803551589277835",
  "return_code" : "SUCCESS",
  "wxappid" : "wxeb99f78727420b07",
  "mch_id" : "1448506702",
  "result_code" : "SUCCESS"
}';
        $result = json_decode($result,1);
        dd($result);
        $a = '';
        dd(isset($a));
        $a = null;
        $b = $a ?? 'ross';
        $e = $a ?: 'ross';
        dump($b);

        $c = isset($d['hello']) ? $a : 'ross';
        dd($c);
        $from_user_id = request()->get('from_user_id') ?? 0;

        $groupGoods = GroupGoods::with('goodsable')->first();
        dd($groupGoods->goodsable);
        dd(234);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
