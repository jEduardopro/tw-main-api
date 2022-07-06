<?php

namespace Tests\Feature\App\Http\Controllers\Tweets;

use App\Models\Reply;
use App\Models\Tweet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TweetRepliesControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_see_the_replies_of_a_tweet()
    {
        $tweet = Tweet::factory()->create();
        Tweet::factory()->count(2)->create();
        Reply::factory()->count(20)->create(["tweet_id" => $tweet->id]);

        $lastTweetReply = Tweet::factory()->create(["created_at" => now()->addMinutes(10)]);
        $reply = Reply::factory()->create(["reply_tweet_id" => $lastTweetReply->id, "tweet_id" => $tweet->id]);

        $response = $this->getJson("api/tweets/{$tweet->uuid}/replies")
                ->assertSuccessful()
                ->assertJsonStructure(["data", "meta", "links"]);

        $mostRecentTweetReply = $response->json("data.0");
        $this->assertEquals($reply->tweetReply->uuid, $mostRecentTweetReply["id"]);
        $this->assertArrayHasKey("owner", $mostRecentTweetReply);
        $this->assertArrayHasKey("image", $mostRecentTweetReply["owner"]);
        $this->assertArrayHasKey("images", $mostRecentTweetReply);
        $this->assertArrayHasKey("retweets_count", $mostRecentTweetReply);
        $this->assertArrayHasKey("replies_count", $mostRecentTweetReply);
        $this->assertArrayHasKey("likes_count", $mostRecentTweetReply);
    }

    /** @test */
    public function the_replies_of_a_tweet_must_fail_if_no_tweet_found()
    {
        $response = $this->getJson("api/tweets/invalid-uuid/replies")
                ->assertStatus(404);

        $this->assertEquals("the tweet does not exist", $response->json("message"));
    }
}
