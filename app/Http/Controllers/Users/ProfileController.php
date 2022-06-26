<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function getProfileByUsername($username)
    {
        $user = User::where('username', $username)->first();

        if (!$user) {
            return $this->responseWithMessage("This account doesn't exist", 400);
        }
        
        if (!$user->is_activated) {
            return $this->responseWithMessage("Account suspended", 400);
        }


        return $this->responseWithResource(ProfileResource::make($user));
    }
}
