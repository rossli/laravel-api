<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TestController extends BaseController
{

    public function index()
    {
        dd(23);
    }
}
