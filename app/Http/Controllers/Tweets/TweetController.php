<?php

namespace App\Http\Controllers\Tweets;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTweetFormRequest;
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

    public function destroy($id)
    {
        $tweet = Tweet::whereId($id)->first();

        if (!$tweet) {
            return $this->responseWithMessage("the tweet does not exist or has already been deleted", 404);
        }

        $tweet->delete();

        return $this->responseWithMessage("tweet removed");
    }
}
