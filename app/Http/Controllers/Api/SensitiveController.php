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
            define('_VIC_WORD_DICT_PATH_',storage_path('dict.igb'));
            $fc = new VicWord('igb');
            $arr = $fc->getWord($q);
            foreach ($arr as $val) {
                $res = Sensitive::where('keyword','LIKE', '%'.$q.'%')->first();
                if ($res) {
                    return $this->success([
                        'sensitive' => TRUE,
                    ]);
                    break;
                }
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
