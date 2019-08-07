<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CourseCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' =>$this->id,
            'title' =>$this->title,
            'image' => config('jkw.cdn_domain') . '/' . $this->cover,
            'subtitle' => $this->subtitle,
            'summary' => $this->summary,
            'is_finished' =>$this->is_finished,
            'is_free' => $this->is_free,
            'price' => $this->price,
            'origin_price' => $this->origin_price,
            'student_num' => $this->student_num,

        ];
    }
}
