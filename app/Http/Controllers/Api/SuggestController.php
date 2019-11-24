<?php

namespace App\Http\Controllers\Api;

use App\Models\Suggest;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SuggestController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'content'=>'required|min:10'
        ],[
            'required'=>'内容不能为空',
            'min'=>'内容过于简短'
        ]);
        if($validator->fails()){
            return $this->failed($validator->errors()->first());
        }
        $result=Suggest::create($request->all());
        //发送模板消息
        $app = Factory::officialAccount(config('wechat.official_account.default'));
         $app->template_message->send([
             'touser' => 'o_ysnwFSWlmpHXi1tWeMs8hH_4TM',
             'template_id' => 'tBOIXW5-RmCaWZIV-KSW5ORuocqpSYVA9BoiVMdOrjs',
             'url' => 'www.baidu.com',
             'data' => [
                 '通知' => 'bug通知',
             ],
         ]);
        return $this->success();
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
