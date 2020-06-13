<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityUser;
use App\Models\Awards;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ActivityUserController extends BaseController
{
    //
    public function store(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'name' => 'required',
            'mobile' => 'required | mobile',
        ], [
            'name.required' => '用户名必填',
            'mobile.required' => '电话必填',
            'mobile.mobile' => '电话格式不正确',
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        }


        ActivityUser::create([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'wechat' => $request->wechat ?? '',
            'awards' => ''
        ]);

        return $this->success('提交成功');
    }


    public function awards()
    {
        $proArr = array(
            array('id'=>1,'name'=>'特等奖','v'=>1),
            array('id'=>2,'name'=>'一等奖','v'=>5),
            array('id'=>3,'name'=>'二等奖','v'=>10),
            array('id'=>4,'name'=>'三等奖','v'=>12),
            array('id'=>5,'name'=>'四等奖','v'=>22),
            array('id'=>6,'name'=>'没中奖','v'=>50)
        );
        foreach ($proArr as $key => $val) {
            $arr[$key] = $val['v'];
        }
        $proSum = array_sum($arr);      // 计算总权重
        $randNum = mt_rand(1, $proSum);
        $d1 = 0;
        $d2 = 0;
        for ($i=0; $i < count($arr); $i++)
        {
            $d2 += $arr[$i];
            if($i==0)
            {
                $d1 = 0;
            }
            else
            {
                $d1 += $arr[$i-1];
            }
            if($randNum >= $d1 && $randNum <= $d2)
            {
                $result = $proArr[$i];
                break; // 注意这里，当我们已经匹配到奖品时，就应该直接退出循环
            }
        }
        unset ($arr);
        return $result;
    }
}
