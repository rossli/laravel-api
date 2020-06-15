<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityUser;
use App\Models\Awards;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
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
            'cookie_id' => 'required'
        ], [
            'name.required' => '用户名必填',
            'cookie_id.required' => 'cookie_id必填',
            'mobile.required' => '电话必填',
            'mobile.mobile' => '电话格式不正确',
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        }
        $awards=Redis::get($request->cookie_id. 'awards');
        if (!$awards) {
            return $this->failed('您还没有抽奖,快去抽奖吧');
        }
        \DB::beginTransaction();
        try {
            $activityUser = ActivityUser::firstOrCreate([
                'mobile' => $request->mobile,
            ],[
                'name' => $request->name,
                'wechat' => $request->wechat ?? '',
            ]);
            Awards::create([
                'activity_user_id' => $activityUser->id,
                'awards' =>$awards,
            ]);
        } catch (Exception $exception) {
            \DB::rollBack();
            return $this->failed('提交失败');
        }
        \DB::commit();

        return $this->success('提交成功');
    }


    public function awards(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'cookie_id' => 'required',
        ], [
            'cookie_id.required' => 'cookie_id必填',
        ]);
        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        }

        //限制 次数
        if (($cookie_count = Redis::get($request->cookie_id)) > 3) {
            if ($cookie_count < 999) {
                Redis::set($request->cookie_id, 999, 'EX', 86400);
            }

            return $this->failed('您今天的次数已经抽完,请明天再来吧');
        }

        $proArr = array(
            array('id' => 1, 'name' => '教材', 'v' => 1),
            array('id' => 2, 'name' => '零基础畅学组合', 'v' => 4),
            array('id' => 3, 'name' => '教资笔试单科突破课', 'v' => 5),
            array('id' => 4, 'name' => '700元抵用卷', 'v' => 24),
            array('id' => 5, 'name' => '600元抵用卷', 'v' => 24),
            array('id' => 6, 'name' => '500元抵用卷', 'v' => 22),
            array('id' => 7, 'name' => '没中奖', 'v' => 15),
            array('id' => 8, 'name' => '没中奖', 'v' => 15)
        );

        if (Redis::get('book') > 50) {
            array_shift($proArr);
        }

        foreach ($proArr as $key => $val) {
            $arr[$key] = $val['v'];
        }
        $proSum = array_sum($arr);      // 计算总权重
        $randNum = mt_rand(1, $proSum);
        $d1 = 0;
        $d2 = 0;
        for ($i = 0; $i < count($arr); $i++) {
            $d2 += $arr[$i];
            if ($i == 0) {
                $d1 = 0;
            } else {
                $d1 += $arr[$i - 1];
            }
            if ($randNum >= $d1 && $randNum <= $d2) {
                $result = $proArr[$i];
                break; // 注意这里，当我们已经匹配到奖品时，就应该直接退出循环
            }
        }
        unset ($arr);

        if ($result['id'] == 1) {
            Redis::Incr('book');
        }
        Redis::Incr($request->cookie_id);

        Redis::set($request->cookie_id . 'awards', $result['name']);

        return $this->success($result);
    }
}
