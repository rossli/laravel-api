<?php

namespace App\Http\Controllers\Api;

use App\Models\CourseMember;
use App\Models\CourseTask;
use App\Models\Teacher;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Rachel\TalkfunSdk\Facades\Talkfun;

class CourseTaskController extends BaseController
{
    //

    public function openCourse(Request $request)
    {
        $courseTask = CourseTask::with('teacher')
            ->where('enabled', 1)
            ->where('is_open', 1)
            ->orderBy('end_time', 'DESC')->get();

        if ($request->limit) {
            $courseTask = $courseTask->take(4);
        }

        $data = [];

        $courseTask->each(function ($item) use (&$data) {
            $teacher = $item->teacher;

            $data[] = [
                'id'=>$item->id,
                'cover' => config('jkw.cdn_domain') . '/' . $teacher->image,
                'name' => $teacher->name,
                'rank' => Teacher::RANK_NAME[$teacher->rank],
                'title' => $item->title,
                'start_time' => $item->start_time,
                'end_time' => $item->end_time,
                'video_poster' => config('jkw.cdn_domain') . '/' .$item->video_poster,
                'type' => $item->type,
            ];
        });

        return $this->success($data);

    }

    public function video(Request $request)
    {
        $user = request()->user();
        if ($user) {
            $course_task = CourseTask::with('course', 'course.material', 'course.task')->find($request->id);

            if (!$course_task) {
                return $this->failed('课程错误,请联系管理员!');
            }

            $course = $course_task->course;
            if (!$course->is_free) {
                $course_member = CourseMember::where('course_id', $course->id)->where('user_id', $user->id)->get();

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
        } else {
            return $this->failed('您还没有登录!');
        }


    }


    public function live(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $task = CourseTask::with('course')->find($request->id);

            $course = $task->course;

            if (!$course->is_free) {
                $course_member = CourseMember::where('course_id', $course->id)->where('user_id', $user->id)->get();
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
                        return $this->failed('直播还未开始,请与' . $task->start_time . '再试', -1);
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
                    }

                    return $this->failed('当前没有直播或回放生成中!');

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
                case CourseTask::TYPE_PSEUDO:
                    $result = Talkfun::courseGet($task->media_id);
                    if ($result['code'] == 0) {
                        //回放
                        $url = $result['data']['playbackUrl'];

                        if (!$result['data']['playback']) {
                            //直播
                            $result = Talkfun::courseAccess($task->media_id, time(), time(), 'role');
                            if ($result['code'] == 0) {
                                $url = $result['data']['liveUrl'];
                            } else {
                                return $this->failed('当前没有直播或回放生成中!');
                            }
                        }
                    } else {
                        return $this->failed('当前没有直播或回放生成中!');
                    }
                    break;
                default:
                    return $this->failed('资源错误!');
            }
            $data = [
                'url' => $url,
            ];
            return $this->success($data);

        } else {
            return $this->failed('您还没有登录!');
        }

    }
}
