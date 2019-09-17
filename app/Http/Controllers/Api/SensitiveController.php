<?php

namespace App\Http\Controllers\Api;

use App\Models\Sensitive;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Lizhichao\Word\VicDict;
use Lizhichao\Word\VicWord;

class SensitiveController extends BaseController
{
    public function index()
    {

        $q = mb_substr(request('q'),0,14);

        if ($q) {
            $res = Sensitive::where('keyword','=', $q)->first();
            if ($res) {
                return $this->success([
                    'sensitive' => TRUE,
                ]);
            }
            return $this->success([
                'sensitive' => FALSE,
            ]);
        }
        return $this->success([
            'sensitive' => FALSE,
        ]);
    }
}
