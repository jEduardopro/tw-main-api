<?php

namespace App\Http\Controllers\Tweets;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Http\Request;

class TweetOwnersRetweetsController extends Controller
{
    public function index($tweetUuid)
    {
        $tweet = Tweet::where("uuid", $tweetUuid)->first();

        $retweets = $tweet->retweets()->get()->pluck('user_id');
        $ownersRetweets = User::whereIn('id', $retweets)
                            ->with(["profileImage"])
                            ->paginate();

        return $this->responseWithResource(ProfileResource::collection($ownersRetweets));
    }
}
