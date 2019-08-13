<?php

namespace App\Http\Controllers\Api;

use App\Models\CourseTask;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Rachel\TalkfunSdk\Facades\Talkfun;

class CourseTaskController extends Controller
{
    //
    public function live(Request $request)
    {
        $task=CourseTask::findOrFail($request->id);

        if ($task->type == CourseTask::TYPE_CLIP) {
            $res = Talkfun::clipGet($task->media_id);
        } else {
            //这里做了兼容, 房间ID以及直播ID都行
            $res = Talkfun::liveRoomGet($task->media_id,$task->start_time);
            if ( !(isset($res['code']) && $res['code'] == 0)) {
                $res = Talkfun::liveGet($task->media_id);
            }
        }
        if (isset($res['code']) && $res['code'] == 0) {
            $url = $res['data']['url'];
        } else {
            $url = '';
        }
        $data[]=[
            'url'=>$url,
        ];
       return $this->success($data);

    }
}
