<?php

namespace App\Http\Controllers\Replies;

use App\Http\Controllers\Controller;
use App\Models\Reply;
use App\Models\Tweet;
use Illuminate\Http\Request;

class RepliesController extends Controller
{
    public function store(Request $request)
    {
        $tweet = Tweet::where("uuid", $request->tweet_id)->first();
        $replyTweet = Tweet::where("uuid", $request->reply_tweet_id)->first();

        Reply::create([
            "tweet_id" => $tweet->id,
            "reply_tweet_id" => $replyTweet->id,
        ]);

        return $this->responseWithMessage("you tweet was sent");
    }

    public function destroy($replyTweetUuid)
    {
        $replyTweet = Tweet::where("uuid", $replyTweetUuid)->first();

        Reply::where('reply_tweet_id', $replyTweet->id)->delete();

        $replyTweet->delete();

        return $this->responseWithMessage("you tweet was deleted");
    }
}
