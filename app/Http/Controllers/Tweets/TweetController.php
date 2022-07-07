<?php

namespace App\Http\Controllers\Tweets;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTweetFormRequest;
use App\Http\Resources\TweetResource;
use App\Models\Tweet;
use Illuminate\Http\Request;

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

        $tweet->load(["user.profileImage", "media"])->loadCount(["replies", "retweets", "likes"]);

        return $this->responseWithResource(TweetResource::make($tweet));
    }

    public function show($tweetUuid)
    {
        $tweet = Tweet::where('uuid', $tweetUuid)
                ->with(["user.profileImage", "media"])
                ->withCount(["replies", "retweets", "likes"])
                ->first();

        if (!$tweet) {
            return $this->responseWithMessage("the tweet does not exist", 404);
        }

        return $this->responseWithResource(TweetResource::make($tweet));
    }

    public function destroy(Request $request, $uuid)
    {
        $user = $request->user();
        $tweet = Tweet::where("uuid", $uuid)->first();


        if (!$tweet) {
            return $this->responseWithMessage("the tweet does not exist or has already been deleted", 404);
        }

        if ($tweet->user_id !== $user->id) {
            return $this->responseWithMessage("you do not have permission to perform this action", 403);
        }
        $tweet->delete();

        $tweet->detachMediaFiles();

        return $this->responseWithMessage("tweet removed");
    }
}
