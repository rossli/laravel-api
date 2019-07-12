<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends BaseController
{

    public function index()
    {
        $user = User::first();

        return $this->success(new UserResource($user));

    }
}
