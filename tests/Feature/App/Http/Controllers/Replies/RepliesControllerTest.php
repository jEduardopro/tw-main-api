<?php

namespace Tests\Feature\App\Http\Controllers\Replies;

use App\Events\DeletedTweetReply;
use App\Events\RepliedTweet;
use App\Models\Reply;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class RepliesControllerTest extends TestCase
{
	use RefreshDatabase;

	/** @test */
	public function an_authenticated_user_can_reply_to_a_tweet()
	{
        Event::fake([RepliedTweet::class]);

        Broadcast::shouldReceive('socket')->andReturn('socket-id');

		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();
		$tweet = Tweet::factory()->create(["user_id" => $user2->id]);
		$myReplyTweet = Tweet::factory()->create(["user_id" => $user->id]);

		Passport::actingAs($user);

		$response = $this->postJson("api/replies", ["tweet_id" => $tweet->uuid, "reply_tweet_id" => $myReplyTweet->uuid]);

		$response->assertSuccessful();

		$this->assertEquals("you tweet was sent", $response->json("message"));

		$this->assertDatabaseHas("replies", [
			"tweet_id" => $tweet->id,
		]);

        $this->assertEquals($tweet->id, $myReplyTweet->fresh()->reply->tweet_id);

        Event::assertDispatched(RepliedTweet::class, function($event) use ($myReplyTweet, $tweet, $user2) {
            $this->assertTrue(!is_null($event->replyTweet));
            $this->assertTrue(!is_null($event->userReplying));

            $this->assertTrue($event->replyTweet->is($myReplyTweet));
            $this->assertTrue($event->userReplying->is($user2));

            $this->assertDontBroadcastToCurrentUser($event);
            $this->assertEventChannelType('public', $event);
            $this->assertEventChannelName("tweets.{$tweet->uuid}.replies", $event);

            return  true;
        });
	}


	/** @test */
	public function an_authenticated_user_can_delete_a_reply_of_a_tweet()
	{
        Event::fake([DeletedTweetReply::class]);

        Broadcast::shouldReceive('socket')->andReturn('socket-id');

		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();
		$tweet = Tweet::factory()->create(["user_id" => $user2->id]);
		$reply = Reply::factory()->create(["tweet_id" => $tweet->id]);
        $myReplyTweet = Tweet::factory()->create(["user_id" => $user->id, "reply_id" => $reply->id]);

		Passport::actingAs($user);

		$this->assertDatabaseHas("replies", [
			"tweet_id" => $tweet->id,
		]);

		$response = $this->deleteJson("api/replies/{$myReplyTweet->uuid}");

		$response->assertSuccessful();

		$this->assertEquals("you tweet was deleted", $response->json("message"));

		$this->assertDatabaseMissing("replies", [
			"tweet_id" => $tweet->id,
		]);

		$this->assertSoftDeleted("tweets", [
			"id" => $myReplyTweet->id,
			"user_id" => $user->id
		]);

        Event::assertDispatched(DeletedTweetReply::class, function($event) use ($tweet) {
            $this->assertTrue(!is_null($event->tweetReplying));

            $this->assertTrue($event->tweetReplying->is($tweet));

            $this->assertDontBroadcastToCurrentUser($event);
            $this->assertEventChannelType('public', $event);
            $this->assertEventChannelName("tweets.{$tweet->uuid}.replies", $event);

            return true;
        });
	}

    /** @test */
    public function an_authenticated_user_can_not_delete_replies_of_a_tweet_that_are_not_yours()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();
        $tweet = Tweet::factory()->create(["user_id" => $user->id]);
        $reply = Reply::factory()->create(["tweet_id" => $tweet->id]);
        $replyTweet = Tweet::factory()->create(["user_id" => $user2->id, "reply_id" => $reply->id]);

        Passport::actingAs($user);

        $this->assertDatabaseHas("replies", [
            "tweet_id" => $tweet->id,
        ]);

        $response = $this->deleteJson("api/replies/{$replyTweet->uuid}");

        $response->assertStatus(403);

        $this->assertEquals("you do not have permission to perform this action", $response->json("message"));
    }

    /** @test */
    public function the_replies_process_to_reply_to_a_tweet_must_fail_if_no_found_the_tweets()
    {
        $user = User::factory()->activated()->create();

        Passport::actingAs($user);

        $response = $this->postJson("api/replies", ["reply_tweet_id" => "reply_uuid", "tweet_id" => "uuid"]);

        $response->assertStatus(400);

        $this->assertEquals("one of the tweets does not exist", $response->json("message"));
    }


    /** @test */
    public function the_replies_process_to_undo_reply_of_a_tweet_must_fail_if_no_found_the_tweet_reply()
    {
        $user = User::factory()->activated()->create();

        Passport::actingAs($user);

		$response = $this->deleteJson("api/replies/reply-uuid");

        $response->assertStatus(404);

        $this->assertEquals("the tweet reply does not exist", $response->json("message"));
    }

    /** @test */
    public function the_reply_tweet_id_is_required()
    {
        $user = User::factory()->activated()->create();

        Passport::actingAs($user);
        $this->postJson("api/replies", ["reply_tweet_id" => null, "tweet_id" => "uuid"])
                ->assertJsonValidationErrorFor("reply_tweet_id");

    }


    /** @test */
    public function the_tweet_id_is_required()
    {
        $user = User::factory()->activated()->create();

        Passport::actingAs($user);
        $this->postJson("api/replies", ["reply_tweet_id" => "uuid", "tweet_id" => null])
                ->assertJsonValidationErrorFor("tweet_id");

    }
}
