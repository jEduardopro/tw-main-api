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
        $replyTweet = Tweet::factory()->create();

        $reply = Reply::factory()->create(["tweet_id" => $tweet->id, "reply_tweet_id" => $replyTweet->id]);
        Tweet::factory()->create(["reply_id" => $reply->id, "created_at" => now()->addMinutes(10)]);

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
