<?php

namespace App\Http\Controllers\Tweets;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTweetFormRequest;
use App\Http\Resources\TweetResource;
use App\Models\Tweet;
use App\Models\User;
use App\Notifications\TweetCreated;
use App\Notifications\UserMentioned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class TweetController extends Controller
{
    public function store(StoreTweetFormRequest $request)
    {
        $user = $request->user();
        $data = $request->only(['body']);
        $peopleMentioned = collect($request->mentions);


        $tweet = new Tweet();
        $tweet->fill($data);
        $tweet->user_id = $user->id;
        $tweet->save();

        $tweet->attachMediaFiles();


        $followers = $user->followers;
        Notification::send($followers, new TweetCreated($tweet));

        if ($peopleMentioned->isNotEmpty()) {
            $usersMentioned = User::whereIn('uuid', $peopleMentioned)->get();
            Notification::send($usersMentioned, new UserMentioned($tweet));
            $tweet->mentions()->attach($usersMentioned);
        }

        $tweet->load(["user.profileImage", "media", "mentions"])->loadCount(["replies", "retweets", "likes"]);

        return $this->responseWithResource(TweetResource::make($tweet));
    }

    public function show($tweetUuid)
    {
        $tweet = Tweet::where('uuid', $tweetUuid)
                ->with(["user.profileImage", "media", "mentions"])
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

        if ($tweet->user_id != $user->id) {
            return $this->responseWithMessage("you do not have permission to perform this action", 403);
        }
        $tweet->delete();

        $tweet->detachMediaFiles();

        return $this->responseWithMessage("tweet removed");
    }
}
