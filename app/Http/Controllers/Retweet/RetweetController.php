<?php

namespace App\Http\Controllers\Retweet;

use App\Http\Controllers\Controller;
use App\Http\Requests\RetweetFormRequest;
use App\Models\Tweet;
use Illuminate\Http\Request;

class RetweetController extends Controller
{
    public function store(RetweetFormRequest $request)
    {
        $user = $request->user();
        $tweetUuid = $request->tweet_id;

        $tweet = Tweet::where("uuid", $tweetUuid)->first();

        if (!$tweet) {
            return $this->responseWithMessage("the tweet you want to retweet does not exist", 404);
        }

        $user->retweet($tweet->id);

        return $this->responseWithMessage("retweet created successfully");
    }

    public function destroy(Request $request, $tweetUuid)
    {
        $user = $request->user();

        $tweet = Tweet::where("uuid", $tweetUuid)->first();

        if (!$tweet) {
            return $this->responseWithMessage("the retweet you want to undo does not exist", 404);
        }

        $retweet = $user->retweets()->where('tweet_id', $tweet->id)->first();

        if (!$retweet) {
            return $this->responseWithMessage("you do not have permission to perform this action", 403);
        }

        $user->undoRetweet($tweet->id);

        return $this->responseWithMessage("retweet deleted successfully");
    }
}
