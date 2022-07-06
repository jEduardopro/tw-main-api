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
}
