<?php

namespace App\Http\Controllers\Friendships;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FriendshipController extends Controller
{
    public function follow(Request $request)
    {
        $user = $request->user();
        $userToFollow = User::where('uuid', $request->user_id)->first();

        if (!Gate::allows('can-follow', $userToFollow)) {
            return $this->responseWithMessage("You can't follow yourself", 403);
        }


        $user->follow($userToFollow->id);

        return $this->responseWithResource(ProfileResource::make($userToFollow));
    }


    public function unfollow(Request $request)
    {
        $user = $request->user();
        $userToUnfollow = User::where('uuid', $request->user_id)->first();

        $user->unfollow($userToUnfollow->id);

        return $this->responseWithMessage("You have successfully unfollowed this user");
    }
}
