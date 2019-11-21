<?php


namespace App\Http\Controllers\Api;


use App\Http\Resources\Api\QrcodeCollection;
use App\Models\Qrcode;

class QrcodeController extends BaseController
{

    public function show($id){
        $result=Qrcode::where('status',1)->where('id',$id)
            ->first();
//        $result=Qrcode::where('status',1)->where('id',$id)->with(['qrcodeCategory'=> function($query){
//             $query->select('id','name','description');
//        }])->first();
        $collection= new QrcodeCollection($result);
        return $collection->additional(['code' => '200',
            'status'=>'success'
        ]);
    }
}
