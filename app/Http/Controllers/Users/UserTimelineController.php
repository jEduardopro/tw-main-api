<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\TweetResource;
use App\Models\User;

class UserTimelineController extends Controller
{
    public function index($userUuid)
    {
        $user = User::active()->where("uuid", $userUuid)->first();

        if (!$user) {
            return $this->responseWithMessage("the timeline of tweets is not available for this account", 400);
        }

        $timeline = $user->tweets()->with(["user.profileImage", "media"])->latest()->paginate();

        return $this->responseWithResource(TweetResource::collection($timeline));
    }
}
