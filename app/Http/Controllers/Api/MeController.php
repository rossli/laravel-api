<?php

namespace App\Http\Controllers\Api;

use App\Models\CourseMember;
use Illuminate\Http\Request;

class MeController extends BaseController
{
    //
    public function course()
    {
        $course_members = CourseMember::with('course')->where([['user_id', '=', request()->user()->id]])->get();

        $data = [];
        $course_members->each(function ($item) use (&$data) {
            $course = $item->course;
            $data[] = [
                'id' => $course->id,
                'image' => config('jkw.cdn_domain') . '/' . $course->cover,
                'title' => $course->title,
            ];
        });
        return $this->success($data);
    }
}
