<?php

namespace Tests\Feature\App\Http\Controllers\Users;

use App\Models\Reply;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UserTweetsAndRepliesTimelineControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_see_a_timeline_of_tweets_and_replies_of_a_specific_profile()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();
        $tweet = Tweet::factory()->create(["user_id" => $user2->id]);
        $reply = Reply::factory()->create(["tweet_id" => $tweet->id, "created_at" => now()->addMinutes(5)]);
        $tweet2 = Tweet::factory()->create(["user_id" => $user->id, "reply_id" => $reply->id, "created_at" => now()->addMinutes(5)]);
        $tweet3 = Tweet::factory()->create(["user_id" => $user->id, "created_at" => now()->addMinutes(15)]);

        $response = $this->getJson("api/users/$user->uuid/tweets-replies-timeline")->assertSuccessful()
                    ->assertJsonStructure(["data", "meta", "links"]);

        $data = $response->json("data.0");
        $this->assertEquals($tweet3->uuid, $data["id"]);
        $this->assertArrayHasKey("reply_to", $response->json("data.1"));
    }

    /** @test */
    public function the_user_tweets_and_replies_timeline_must_fail_if_no_user_found()
    {
        $response = $this->getJson("api/users/invalid-uuid/tweets-replies-timeline")
                ->assertStatus(404);

        $this->assertEquals("the timeline of tweets and replies is not available for this account", $response->json("message"));
    }
}
