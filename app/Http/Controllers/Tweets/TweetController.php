<?php

namespace App\Http\Controllers\Tweets;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTweetFormRequest;
use App\Http\Resources\TweetResource;
use App\Models\Tweet;

class TweetController extends Controller
{
    public function store(StoreTweetFormRequest $request)
    {
        $user = $request->user();
        $data = $request->only(['body']);


        $tweet = new Tweet();
        $tweet->fill($data);
        $tweet->user_id = $user->id;
        $tweet->save();

        $tweet->attachMediaFiles();

        return $this->responseWithMessage("successful tweet");
    }

    public function show($tweetUuid)
    {
        $tweet = Tweet::where('uuid', $tweetUuid)
                ->with(["user.profileImage", "media"])
                ->withCount(["retweets", "likes"])
                ->first();

        if (!$tweet) {
            return $this->responseWithMessage("the tweet does not exist", 404);
        }

        return $this->responseWithResource(TweetResource::make($tweet));
    }

    public function destroy($uuid)
    {
        $tweet = Tweet::where("uuid", $uuid)->first();

        if (!$tweet) {
            return $this->responseWithMessage("the tweet does not exist or has already been deleted", 404);
        }

        $tweet->delete();

        return $this->responseWithMessage("tweet removed");
    }
}
