<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\TweetResource;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserTimelineController extends Controller
{
    public function index(Request $request, $userUuid)
    {
        $user = User::active()->where("uuid", $userUuid)->first();

        if (!$user) {
            return $this->responseWithMessage("the timeline of tweets is not available for this account", 404);
        }

        $tweetsAndRetweets = DB::table(DB::raw("(
                select t.id, t.uuid, t.user_id, t.body, t.created_at from tweets as t where t.user_id = {$user->id}
                union
                select t.id, t.uuid, t.user_id, t.body, rt.created_at from tweets as t
                inner join retweets as rt on rt.tweet_id = t.id where rt.user_id = {$user->id}) a
                order by created_at desc"
            ))->toSql();

        $tweets = Tweet::fromQuery($tweetsAndRetweets)
                    ->map(function($tweet) {
                        return $tweet->load(['user.profileImage', 'media'])->loadCount(["retweets", "replies", "likes"]);
                    });

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = $request->per_page ?? 15;
        $results = $tweets->slice(($page - 1) * $perPage, $perPage)->values();
        $timeline =  new LengthAwarePaginator($results, $tweets->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
        $timeline->appends(request()->all());

        return $this->responseWithResource(TweetResource::collection($timeline));
    }
}
