<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\TweetResource;
use Illuminate\Http\Request;

class UserTimelineController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $timeline = $user->tweets()->latest()->paginate();

        return $this->responseWithResource(TweetResource::collection($timeline));
    }
}
