<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\TweetResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserTweetsAndRepliesTimelineController extends Controller
{
    public function index($userUuid)
    {
        $user = User::where("uuid", $userUuid)->first();

        if (!$user) {
            return $this->responseWithMessage("the timeline of tweets and replies is not available for this account", 404);
        }

        $tweetsAndReplies = $user->tweets()
                        ->with([
                            "user.profileImage", "media",
                            "mentions",
                            "reply.tweet" => function ($q) {
                                $q->with(["user.profileImage", "media", "mentions"])
                                    ->withCount(["replies", "retweets", "likes"]);
                            }
                        ])
                        ->withCount(["replies", "retweets", "likes"])
                        ->latest()->paginate();

        return $this->responseWithResource(TweetResource::collection($tweetsAndReplies));
    }
}
