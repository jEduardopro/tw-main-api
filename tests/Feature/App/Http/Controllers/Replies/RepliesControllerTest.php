<?php

namespace Tests\Feature\App\Http\Controllers\Replies;

use App\Models\Reply;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class RepliesControllerTest extends TestCase
{
	use RefreshDatabase;

	/** @test */
	public function an_authenticated_user_can_reply_to_a_tweet()
	{
		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();
		$tweet = Tweet::factory()->create(["user_id" => $user2->id]);
		$myReplyTweet = Tweet::factory()->create(["user_id" => $user->id]);

		Passport::actingAs($user);

		$response = $this->postJson("api/replies", ["reply_tweet_id" => $myReplyTweet->uuid, "tweet_id" => $tweet->uuid]);

		$response->assertSuccessful();

		$this->assertEquals("you tweet was sent", $response->json("message"));

		$this->assertDatabaseHas("replies", [
			"tweet_id" => $tweet->id,
			"reply_tweet_id" => $myReplyTweet->id
		]);
	}


	/** @test */
	public function an_authenticated_user_can_delete_a_reply_of_a_tweet()
	{
		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();
		$tweet = Tweet::factory()->create(["user_id" => $user2->id]);
		$myReplyTweet = Tweet::factory()->create(["user_id" => $user->id]);
		Reply::factory()->create([
			"tweet_id" => $tweet->id,
			"reply_tweet_id" => $myReplyTweet->id
		]);

		Passport::actingAs($user);

		$this->assertDatabaseHas("replies", [
			"tweet_id" => $tweet->id,
			"reply_tweet_id" => $myReplyTweet->id
		]);

		$response = $this->deleteJson("api/replies/{$myReplyTweet->uuid}");

		$response->assertSuccessful();

		$this->assertEquals("you tweet was deleted", $response->json("message"));

		$this->assertDatabaseMissing("replies", [
			"tweet_id" => $tweet->id,
			"reply_tweet_id" => $myReplyTweet->id
		]);

		$this->assertSoftDeleted("tweets", [
			"id" => $myReplyTweet->id,
			"user_id" => $user->id
		]);
	}

    /** @test */
    public function an_authenticated_user_can_not_delete_replies_of_a_tweet_that_are_not_yours()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();
        $tweet = Tweet::factory()->create(["user_id" => $user->id]);
        $replyTweet = Tweet::factory()->create(["user_id" => $user2->id]);
        Reply::factory()->create([
            "tweet_id" => $tweet->id,
            "reply_tweet_id" => $replyTweet->id
        ]);

        Passport::actingAs($user);

        $this->assertDatabaseHas("replies", [
            "tweet_id" => $tweet->id,
            "reply_tweet_id" => $replyTweet->id
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
