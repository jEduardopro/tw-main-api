<?php

namespace Tests\Feature\App\Http\Controllers\Home;

use App\Models\Reply;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class HomeTimelineControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_authenticated_user_can_see_their_tweets_and_the_tweets_of_the_people_they_follow()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);
        $user2 = User::factory()->activated()->create();
        User::factory()->count(20)->create();

        Tweet::factory()->count(5)->create(["user_id" => $user2->id]);
        $myTweet = Tweet::factory()->create(["user_id" => $user->id, "created_at" => now()->addMinutes(10)]);

        $response = $this->getJson("api/home/timeline")->assertSuccessful()->assertJsonStructure(["data", "meta", "links"]);

        $this->assertEquals(1, count($response->json("data")));

        $user->follow($user2->id);
        $tweet = Tweet::factory()->create(["user_id" => $user2->id, "created_at" => now()->addMinutes(20)]);
        $lastTweet = Tweet::factory()->create(["user_id" => $user->id, "created_at" => now()->addMinutes(30)]);
        $reply = Reply::factory()->create(["tweet_id" => $tweet->id, "created_at" => now()->addMinutes(5)]);
        $lastTweet->reply_id = $reply->id;
        $lastTweet->save();

        $response = $this->getJson("api/home/timeline")->assertSuccessful()->assertJsonStructure(["data", "meta", "links"]);

        $data = $response->json("data");
        $this->assertEquals(8, count($data));
        $this->assertEquals($lastTweet->uuid, $data["0"]["id"]);
        $this->assertEquals($tweet->uuid, $data["1"]["id"]);

        $lastTweetData = $data["0"];
        $this->assertArrayHasKey("owner", $lastTweetData);
        $this->assertArrayHasKey("image", $lastTweetData["owner"]);
        $this->assertArrayHasKey("images", $lastTweetData);
        $this->assertArrayHasKey("mentions", $lastTweetData);
        $this->assertArrayHasKey("retweets_count", $lastTweetData);
        $this->assertArrayHasKey("replies_count", $lastTweetData);
        $this->assertArrayHasKey("reply_to", $lastTweetData);
        $this->assertArrayHasKey("likes_count", $lastTweetData);
        $this->assertArrayHasKey("liked", $lastTweetData);
    }
}
