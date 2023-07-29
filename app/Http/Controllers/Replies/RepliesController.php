<?php

namespace App\Http\Controllers\Replies;

use App\Events\DeletedTweetReply;
use App\Events\RepliedTweet;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReplyTweetFormRequest;
use App\Models\Reply;
use App\Models\Tweet;
use Illuminate\Http\Request;

class RepliesController extends Controller
{
    public function store(ReplyTweetFormRequest $request)
    {
        $tweet = Tweet::where("uuid", $request->tweet_id)->first();
        $replyTweet = Tweet::where("uuid", $request->reply_tweet_id)->first();

        if (!$tweet || !$replyTweet) {
            return $this->responseWithMessage("one of the tweets does not exist", 400);
        }

        $reply = Reply::firstOrCreate(["tweet_id" => $tweet->id, "reply_tweet_id" => $replyTweet->id]);
        $replyTweet->reply_id = $reply->id;
        $replyTweet->save();

        RepliedTweet::dispatch($replyTweet, $tweet->user);

        return $this->responseWithMessage("you tweet was sent");
    }

    public function destroy(Request $request, $replyTweetUuid)
    {
        $user = $request->user();

        $replyTweet = Tweet::where("uuid", $replyTweetUuid)->first();

        if (!$replyTweet) {
            return $this->responseWithMessage("the tweet reply does not exist", 404);
        }

        if ($replyTweet->user_id != $user->id) {
            return $this->responseWithMessage("you do not have permission to perform this action", 403);
        }

        $tweetReplying = $replyTweet->reply->tweet;

        $replyTweet->reply->delete();

        $user->notificationsSent()->where('data->reply_tweet_uuid', $replyTweet->uuid)->delete();

        $replyTweet->delete();

        $replyTweet->detachMediaFiles();

        DeletedTweetReply::dispatch($tweetReplying);

        return $this->responseWithMessage("you tweet was deleted");
    }
}
