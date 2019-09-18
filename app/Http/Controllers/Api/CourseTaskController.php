<?php

namespace App\Http\Controllers\Api;

use App\Models\CourseMember;
use App\Models\CourseTask;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Rachel\TalkfunSdk\Facades\Talkfun;

class CourseTaskController extends BaseController
{
    //

    public function video(Request $request)
    {

        $course_task = CourseTask::with('course', 'course.material', 'course.task')->find($request->id);


        if (!$course_task) {
            return $this->failed('课程错误,请联系管理员!');
        }

        $course = $course_task->course;
        if (!$course->is_free) {
            $course_member = CourseMember::where('course_id', $course->id)->where('user_id', request()->user()->id)->get();

            if (!$course_member) {
                return $this->failed('您还不能购买课程,不能观看哦!');
            }

        }

        $task = [];
        $material = [];
        $course->task->each(function ($item) use (&$task) {
            $task[] = [
                'id' => $item->id,
                'title' => $item->title,
                'is_free' => $item->is_free,
                'type' => $item->type,
                'media_id' => $item->media_id,
            ];
        });
        $course->material->each(function ($item) use (&$material) {
            $material[] = [
                'id' => $item->id,
                'title' => $item->title,
                'size' => $item->size,
                'description' => $item->description,
            ];
        });

        $data = [
            'media_source' => $course_task->media_source ? config('jkw.cdn_domain') . '/' . $course_task->media_source : '',
            'video_poster' => $course_task->video_poster ? config('jkw.cdn_domain') . '/' . $course_task->video_poster : '',
            'summary' => $course->summary,
            'task' => $task,
            'material' => $material,
        ];

        return $this->success($data);

    }


    public function live(Request $request)
    {
        $user = request()->user();

        $task = CourseTask::with('course')->find($request->id);

        $course = $task->course;
        if (!$course->is_free) {
            $course_member = CourseMember::where('course_id', $course->id)->where('user_id', request()->user()->id)->get();

            if (!$course_member) {
                return $this->failed('您还不能购买课程,不能观看哦!');
            }

        }

        if (!$task) {
            return $this->failed('没有该课程!');
        }
        switch ($task->type) {
            case CourseTask::TYPE_LIVE:
                if ($task->start_time > now()) {
                    return back()->withErrors('直播还未开始,请与' . $task->start_time . '再试');
                }
                $res = Talkfun::roomGetInfo($task->media_id);
                if ($res['code'] == 0 && !empty($res['data'])) {
                    if ($res['data']['playing']) {
                        $res = Talkfun::userAccess($user->id, $user->nick_name, 'user', $task->media_id);
                        if ($res['code'] == 0 && !empty($res['data'])) {
                            $url = $res['data']['roomUrl'] . '&access_token=' . $res['data']['access_token'];
                            break;
                        }
                    }
                } else {
                    return $this->failed('当前没有直播或回放生成中!');
                }

            case CourseTask::TYPE_PLAYBACK:
                $res = Talkfun::liveGet($task->media_id);
                if ($res['code'] == 0 && !empty($res['data'])) {
                    $url = $res['data']['url'];
                    break;
                } else {
                    return $this->failed('回放生成中!');
                }
            case CourseTask::TYPE_DOWNLOAD:
                $url = config('filesystems.disks.oss.cdnDomain') . '/' . $task->media_source;
                break;
            case CourseTask::TYPE_CLIP:
                $res = Talkfun::clipGet($task->media_id);
                if ($res['code'] == 0 && !empty($res['data'])) {
                    $url = $res['data']['url'];
                }
                break;
            default:
                return $this->failed('资源错误!');
        }
        $data = [
            'url' => $url,
        ];
        return $this->success($data);

    }
}
