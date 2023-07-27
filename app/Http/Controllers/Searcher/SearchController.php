<?php

namespace App\Http\Controllers\Searcher;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\TweetResource;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->f;
        $q = $request->q;
        $results = [];
        switch ($filter) {
            case 'user':
                $users = User::search($q)->with(['profileImage', 'followers:id'])->paginate();
                $results = ProfileResource::collection($users);
                break;
            case 'image':
                $tweets = Tweet::searchByTerm($q)
                        ->has("media")
                        ->with(['media', 'user.profileImage'])
                        ->withCount(["retweets", "replies", "likes"])
                        ->latest()
                        ->paginate();
                $results = TweetResource::collection($tweets);
                break;
            default:
                $users = User::search($q)->with(['profileImage', 'followers:id'])->paginate();
                $results = ProfileResource::collection($users);

        }

        return $this->responseWithResource($results);
    }
}
