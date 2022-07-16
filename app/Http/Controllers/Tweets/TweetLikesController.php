<?php

namespace App\Http\Controllers\Tweets;

use App\Http\Controllers\Controller;
use App\Models\Tweet;
use Illuminate\Http\Request;

class TweetLikesController extends Controller
{
    public function store($tweetUuid)
    {
        $tweet = Tweet::where("uuid", $tweetUuid)->first();

        if (!$tweet) {
            return $this->responseWithMessage("the tweet does not exist", 404);
        }

        if ($tweet->likes()->where(["user_id" => request()->user()->id])->exists()) {
            return $this->responseWithMessage("you already liked this tweet");
        }

        $tweet->like();

        return $this->responseWithMessage("like tweet done");
    }


    public function destroy(Request $request, $tweetUuid)
    {
        $user = $request->user();

        $tweet = Tweet::where("uuid", $tweetUuid)->first();

        if (!$tweet) {
            return $this->responseWithMessage("the tweet does not exist", 404);
        }

        $like = $tweet->likes()->where(["user_id" => $user->id])->first();

        if (!$like) {
            return $this->responseWithMessage("you do not have permission to perform this action", 403);
        }

        $tweet->unlike();

        $tweet->user->notifications()->where('data->like_sender_uuid', $user->uuid)->delete();

        return $this->responseWithMessage("unlike tweet done");
    }
}
