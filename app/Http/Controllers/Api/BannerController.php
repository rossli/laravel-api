<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\BannerCollection;
use App\Models\Banner;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BannerController extends BaseController
{

    public function index()
    {
        $banners = Banner::where([
            ['pos', '=', 0],
            ['enabled', '=', 1],
            ['attr', '=', Banner::ATTR_MINI],
        ])->orderBy('updated_at','DESC')->get();

        $data = [];
        $banners->each(function ($item) use (&$data) {
            $data[] = [
                'image' => config('jkw.cdn_domain') . '/' . $item->image,
                'url' => $item->url,
            ];
        });

        return $this->success($data);
    }

    public function indexH5()
    {
        $banners = Banner::where([
            ['pos', '=', 0],
            ['enabled', '=', 1],
            ['attr', '=', Banner::ATTR_H5],
        ])->orderBy('updated_at','DESC')->get();

        $data = [];
        $banners->each(function ($item) use (&$data) {
            $data[] = [
                'image' => config('jkw.cdn_domain') . '/' . $item->image,
                'url' => $item->url,

            ];
        });

        return $this->success($data);
    }

    public function adH5()
    {
        $banner=Banner::where([
            ['pos','=',Banner::ME],
            ['enabled','=','3'],
            ['attr', '=', Banner::ATTR_H5]
        ])->orderBy('updated_at','DESC')->first();
        if($banner){
            $data=[
                'image'=>config('jkw.cdn_domain') . '/' . $banner->image,
                'url'=>  $banner->url,
            ];
            return $this->success($data);
        }
        return $this->failed('没有数据');

    }


}
