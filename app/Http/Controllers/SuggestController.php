<?php

namespace App\Http\Controllers;

use App\Models\Suggest;
use Illuminate\Http\Request;

class SuggestController extends Controller
{
    public function list()
    {
        $suggest = Suggest::orderBy('id','DESC')->limit(100)->get();
        return view('suggest.list',compact('suggest'));
    }
}
