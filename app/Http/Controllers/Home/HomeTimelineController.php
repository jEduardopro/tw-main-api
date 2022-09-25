<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Http\Resources\TweetResource;
use App\Models\Tweet;
use Illuminate\Http\Request;

class HomeTimelineController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $followings = $user->following()->get()->pluck('id');
        $followings->push($user->id);

        $tweets = Tweet::whereIn('user_id', $followings)
                ->with(["user.profileImage", "media", "mentions.profileImage"])
                ->withCount(["retweets", "replies", "likes"])
                ->latest()->paginate();

        return $this->responseWithResource(TweetResource::collection($tweets));
    }
}
