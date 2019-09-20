<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\CourseCollection;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CourseMember;
use App\Models\CourseTask;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CourseController extends BaseController
{

    //公开课程
    public function open(Request $request)
    {
        $all = $request->all;
        $category = Category::with('course')->find(9);
        if ($all) {
            $course = $category->course;
        } else {
            $course = $category->course->take(6);
        }
        $data = [];
        $course->each(function ($item) use (&$data) {
            $data[] = [
                'image' => config('jkw.cdn_domain') . '/' . $item->cover,
                'title' => $item->title,
                'id' => $item->id,
                'is_free' => $item->price == 0 || $item->is_free == 1,
                'price' => $item->price,
                'is_finished' => $item->is_finished,
                'subtitle' => $item->subtitle,
                'student_sum' => $item->student_add + $item->student_num,
            ];
        });

        return $this->success($data);
    }

    //推荐课程
    public function recommend(Request $request)
    {
        $all = $request->all;
        $courses = Course::where([
            ['enabled', '=', '1'],
            ['is_recommend', '=', '1'],
        ])->get();
        if (!$all) {
            $courses = $courses->take(6);
        }
        $data = [];
        $courses->each(function ($item) use (&$data) {
            $data[] = [
                'image' => config('jkw.cdn_domain') . '/' . $item->cover,
                'title' => $item->title,
                'id' => $item->id,
                'is_free' => $item->price == 0 || $item->is_free == 1,
                'price' => $item->price,
                'is_finished' => $item->is_finished,
                'subtitle' => $item->subtitle,
                'student_sum' => $item->student_add + $item->student_num,
            ];
        });

        return $this->success($data);
    }

    public function show($id)
    {

        $course = Course::with([
            'task' => function ($query) {
                $query->select('id', 'title', 'is_free', 'type', 'media_id', 'course_id');
            },
            'material',
        ])->find($id);
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
            'image' => config('jkw.cdn_domain') . '/' . $course->cover,
            'title' => $course->title,
            'subtitle' => $course->subtitle,
            'id' => $course->id,
            'is_free' => $course->price == 0 || $course->is_free == 1,
            'price' => $course->price,
            'is_finished' => $course->is_finished,
            'student_num' => $course->student_num,
            'student_add' => $course->student_add,
            'student_sum' => $course->student_add + $course->student_num,
            'origin_price' => $course->origin_price,
            'summary' => $course->summary,
            'short_intro' => $course->short_intro,
            'is_group' => $course->is_group,
            'task' => $task,
            'material' => $material,
        ];

        return $this->success($data);
    }

    public function list()
    {
        $category = Category::with('course')->get();
        $data = [];
        $i = 0;
        $category->each(function ($item) use (&$data, &$i) {
            $course = $item->course;
            $course->each(function ($it) use (&$data, $i) {
                $data[$i][] = [
                    'image' => config('jkw.cdn_domain') . '/' . $it->cover,
                    'title' => $it->title,
                    'subtitle' => $it->subtitle,
                    'id' => $it->id,
                    'is_free' => $it->price == 0 || $it->is_free == 1,
                    'price' => $it->price,
                    'is_finished' => $it->is_finished,
                    'origin_price' => $it->origin_price,
                ];
            });
            $i++;
        });

        return $this->success($data);
    }

    public function join($id)
    {
        $user_id = request()->user()->id;
        $course = Course::find($id);
        if (!$course) {
            return $this->failed('参数错误');
        }
        //判断是否是免费课程
        if (!$course->canJoin()) {
            return $this->failed('此课程非免费课程,请购买课程');
        }

        //判断是否是课程学员
        $course_member = CourseMember::where('user_id', $user_id)->where('course_id', $id)->first();
        if (!$course_member) {
            CourseMember::create([
                'user_id' => $user_id,
                'course_id' => $id,
            ]);
            $course->student_num++;
            $course->save();

            return $this->success('加入成功!');
        }

        return $this->failed('您已是此课程学员');

    }

    public function guide()
    {
        $category = Category::with('course')->where('parent_id', 1)->get();
        $data = [];
        $category->each(function ($item) use (&$data) {
            $course = $item->course;
            $course->each(function ($item) use (&$data) {
                $data[] = [
                    'image' => config('jkw.cdn_domain') . '/' . $item->cover,
                    'title' => $item->title,
                    'id' => $item->id,
                    'is_free' => $item->price == 0 || $item->is_free == 1,
                    'price' => $item->price,
                    'is_finished' => $item->is_finished,
                    'subtitle' => $item->subtitle,
                    'student_sum' => $item->student_add + $item->student_num,
                ];
            });
        });

        return $this->success($data);
    }
}
