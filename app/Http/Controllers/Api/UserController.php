<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends BaseController
{

    public function index(Request $request)
    {
        $user = $request->user();
        return $this->success(new UserResource($user));
    }

}
