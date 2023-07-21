<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Models\User;

class UserFollowingsController extends Controller
{
    public function index($userUuid)
    {
        $user = User::where("uuid", $userUuid)->first();

        if (!$user) {
            return $this->responseWithMessage("the followings list is not available for this user account", 400);
        }

        $followings = $user->following()->orderBy('followers.created_at', 'desc')
                    ->with(['profileImage', 'followers:id'])->paginate();

        return $this->responseWithResource(ProfileResource::collection($followings));
    }
}
