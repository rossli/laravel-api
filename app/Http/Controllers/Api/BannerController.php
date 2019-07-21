<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\BannerCollection;
use App\Models\Banner;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BannerController extends Controller
{

    public function index()
    {
        return new BannerCollection(Banner::where([
            ['pos', '=', 0],
            ['enabled', '=', 1],
            ['attr', '=', Banner::ATTR_MINI],
        ])->get());
    }
}
