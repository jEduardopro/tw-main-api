<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserFollowingsController extends Controller
{
    public function index($userUuid)
    {
        $user = User::where("uuid", $userUuid)->first();

        if (!$user) {
            return $this->responseWithMessage("the followings list is not available for this user account", 400);
        }

        $followings = Cache::remember("user_{$user->id}_followings_list", 900, function () use ($user) {
            return $user->following()->orderBy('followers.created_at', 'desc')->with(['profileImage'])->paginate();
        });


        return $this->responseWithResource(ProfileResource::collection($followings));
    }
}
