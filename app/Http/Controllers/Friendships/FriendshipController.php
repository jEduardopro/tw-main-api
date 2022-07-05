<?php

namespace App\Http\Controllers\Friendships;

use App\Http\Controllers\Controller;
use App\Http\Requests\FriendshipFormRequest;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class FriendshipController extends Controller
{
    public function follow(FriendshipFormRequest $request)
    {
        $user = $request->user();
        $userToFollow = User::where('uuid', $request->user_id)->first();

        if (!$userToFollow) {
            return $this->responseWithMessage("the user account does not exist", 400);
        }

        if (!Gate::allows('can-follow', $userToFollow)) {
            return $this->responseWithMessage("You can't follow yourself", 403);
        }

        $followings = $user->following()->select('users.id')->get()->pluck('id');
        if ($followings->contains($userToFollow->id)) {
            return $this->responseWithMessage("you are already following this user", 400);
        }

        $user->follow($userToFollow->id);

        Cache::forget("user_{$userToFollow->id}_followers_list");
        Cache::forget("user_{$user->id}_followings_list");

        return $this->responseWithResource(ProfileResource::make($userToFollow));
    }


    public function unfollow(FriendshipFormRequest $request)
    {
        $user = $request->user();
        $userToUnfollow = User::where('uuid', $request->user_id)->first();

        if (!$userToUnfollow) {
            return $this->responseWithMessage("the user account does not exist", 400);
        }

        $user->unfollow($userToUnfollow->id);

        Cache::forget("user_{$userToUnfollow->id}_followers_list");
        Cache::forget("user_{$user->id}_followings_list");

        return $this->responseWithMessage("You have successfully unfollowed this user");
    }
}
