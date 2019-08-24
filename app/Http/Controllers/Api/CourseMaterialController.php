<?php

namespace App\Http\Controllers\Api;

use App\Models\CourseMaterial;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CourseMaterialController extends BaseController
{
    //
    public function show($id)
    {
        $material = CourseMaterial::findOrFail($id);
        $data= [
            'id' => $material->id,
            'path' => config('jkw.cdn_domain') . '/' .$material->path,
            'title' => $material->title,
            'size' => $material->size,
            'description' =>$material->description,
        ];

        return $this->success($data);

    }

}
