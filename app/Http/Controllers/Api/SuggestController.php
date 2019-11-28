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
        $html = view('suggest.index');
        return response($html)->getContent();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|min:8',
            'contact' => 'required'
        ], [
            'content.required' => '内容不能为空',
            'content.min' => '内容过于简短',
            'contact.required' => '请输入您的联系方式'
        ]);
        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        }
        $result = Suggest::create($request->all());

        //发送模板消息
        $app = Factory::officialAccount(config('wechat.official_account.default'));
        $admin = ['o_ysnwFSWlmpHXi1tWeMs8hH_4TM', 'o_ysnwFHdBWTZ0gmeaAFx6aRh_10', 'o_ysnwMa8RsUaaQkk-HdftfXa7p0'];
        foreach ($admin as $item) {
            $app->template_message->send([
                'touser' => $item,
                'template_id' => 'CaC_wtfBzuII_vrYrewwPFnYPyj5UvbfooIMCJkzJVs',
                'url' => route('suggest.list'),
                'data' => [
                    'keyword1' =>  "问题反馈",
                    'keyword2' => Suggest::TYPE[$result->type],
                    'keyword3' => '技术部',
                    'keyword4' => '技术部',
                ],
            ]);
        }

        return $this->success('感谢您的反馈,我们将尽快处理!');


    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
