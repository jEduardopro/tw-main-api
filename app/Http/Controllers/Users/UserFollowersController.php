<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserFollowersController extends Controller
{
    public function index($userUuid)
    {
        $user = User::where("uuid", $userUuid)->first();

        if (!$user) {
            return $this->responseWithMessage("the followers list is not available for this user account", 400);
        }

        $followers = Cache::remember("user_{$user->id}_followers_list", 900, function () use ($user) {
            return $user->followers()->orderBy('followers.created_at', 'desc')
                ->with(['profileImage', 'followers:id,uuid,name,username'])->paginate();
        });


        return $this->responseWithResource(ProfileResource::collection($followers));
    }
}
