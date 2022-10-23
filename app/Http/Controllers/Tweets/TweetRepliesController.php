<?php

namespace App\Http\Controllers\Tweets;

use App\Http\Controllers\Controller;
use App\Http\Resources\TweetResource;
use App\Models\Tweet;
use Illuminate\Http\Request;

class TweetRepliesController extends Controller
{
    public function index(Request $request, $tweetUuid)
    {
        $tweet = Tweet::where("uuid", $tweetUuid)->first();

        if (!$tweet) {
            return $this->responseWithMessage("the tweet does not exist", 404);
        }

        $replies = $tweet->tweetReplies()
                    ->with(["user.profileImage", "media", "mentions"])
                    ->withCount(["retweets", "replies", "likes"])
                    ->latest()->paginate();

        return $this->responseWithResource(TweetResource::collection($replies));
    }
}
