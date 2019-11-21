<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class QrcodeCollection extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'category_name' =>$this->qrcode_category,
            'description' =>$this->description,
            'start_time' =>$this->start_time,
            'end_time' =>$this->end_time,
            'remark' =>$this->remark,
            'join_number'=>$this->number+$this->basic_number,
            'image' => config('jkw.cdn_domain') . '/' . $this->image,
        ];
    }
}
