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
        return $this->success($category->course);
    }

    //推荐课程
    public function recommend()
    {
        $courses=Course::where([
            ['enabled', '=', '1'],
            ['is_recommend', '=', '1']
        ])->limit(6)->get();
        return $courses;

    }

    public function show($id)
    {
        $course = Course::with('task','material','classroom')->find($id);
        return $course;
    }
}
