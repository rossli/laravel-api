<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\CourseCollection;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CourseTask;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CourseController extends BaseController
{
    //公开课程
    public function open()
    {
        $category = Category::with('course')->find(9);
        $course = $category->course->take(6);
        $data = [];
        $course->each(function ($item) use (&$data) {
            $data[] = [
                'image' => config('jkw.cdn_domain') . '/' . $item->cover,
                'title' => $item->title,
                'id' => $item->id,
                'is_free' => $item->is_free,
                'price' => $item->price,
                'is_finished' => $item->is_finished,
            ];
        });
        return $this->success($data);
    }

    //推荐课程
    public function recommend()
    {
        $courses = Course::where([
            ['enabled', '=', '1'],
            ['is_recommend', '=', '1']
        ])->limit(6)->get();
        $data = [];
        $courses->each(function ($item) use (&$data) {
            $data[] = [
                'image' => config('jkw.cdn_domain') . '/' . $item->cover,
                'title' => $item->title,
                'id' => $item->id,
                'is_free' => $item->is_free,
                'price' => $item->price,
                'is_finished' => $item->is_finished,
            ];
        });
        return $this->success($data);
    }

    public function show($id)
    {
        $course = Course::with('task', 'material')->find($id);
        $data = [];
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
                'path' => $item->path,
            ];
        });
        $data[] = [
            'image' => config('jkw.cdn_domain') . '/' . $course->cover,
            'title' => $course->title,
            'id' => $course->id,
            'is_free' => $course->is_free,
            'price' => $course->price,
            'is_finished' => $course->is_finished,
            'student_num' => $course->student_num,
            'origin_price' => $course->origin_price,
            'summary' => $course->summary,
            'short_intro' => $course->short_intro,
            'task' => $task,
            'material' => $material,

        ];
        return $this->success($data);
    }

    public function list()
    {
        $category = Category::with('course')->get();
        $data=[];
        $i=0;
        $category->each(function ($item) use (&$data,&$i) {
            $course=$item->course;
            $course->each(function ($it) use (&$data,$i){
               $data[$i][]=[
                   'image' => config('jkw.cdn_domain') . '/' . $it->cover,
                   'title' => $it->title,
                   'subtitle' => $it->subtitle,
                   'id' => $it->id,
                   'is_free' => $it->is_free,
                   'price' => $it->price,
                   'is_finished' => $it->is_finished,
               ];
           });
           $i++;
        });
        return $this->success($data);
    }
}
