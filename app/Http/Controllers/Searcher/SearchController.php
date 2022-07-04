<?php

namespace App\Http\Controllers\Searcher;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\TweetResource;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->f;
        $q = $request->q;
        $results = [];
        switch ($filter) {
            case 'user':
                $users = User::search($q)->paginate();
                $results = ProfileResource::collection($users);
                break;
            case 'image':
                $tweets = Tweet::searchByImageTerm($q)->with(['media', 'user'])->paginate();
                $results = TweetResource::collection($tweets);
                break;

        }

        return $this->responseWithResource($results);
    }
}