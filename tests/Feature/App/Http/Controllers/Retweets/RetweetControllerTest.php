<?php

namespace Tests\Feature\App\Http\Controllers\Retweets;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class RetweetControllerTest extends TestCase
{

	use RefreshDatabase;

	/** @test */
	public function an_authenticated_user_can_retweet_a_tweet()
	{
		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();

		$tweet = Tweet::factory()->create(["user_id" => $user2->id]);

		Passport::actingAs($user);

		$response = $this->postJson("api/retweets", ["tweet_id" => $tweet->uuid]);

		$response->assertSuccessful();

		$this->assertEquals("retweet created successfully", $response->json("message"));

		$this->assertDatabaseHas("retweets", [
			"user_id" => $user->id,
			"tweet_id" => $tweet->id
		]);
	}


	/** @test */
	public function an_authenticated_user_can_undo_a_retweet_of_a_tweet()
	{
		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();

		$tweet = Tweet::factory()->create(["user_id" => $user2->id]);

		$user->retweet($tweet->id);

		Passport::actingAs($user);

		$response = $this->deleteJson("api/retweets/{$tweet->uuid}");

		$response->assertSuccessful();

		$this->assertEquals("retweet deleted successfully", $response->json("message"));

		$this->assertDatabaseMissing("retweets", [
			"user_id" => $user->id,
			"tweet_id" => $tweet->id
		]);
	}

	/** @test */
	public function the_retweet_process_must_fail_if_no_tweet_found()
	{
		$user = User::factory()->activated()->create();

		Passport::actingAs($user);

		$response = $this->postJson("api/retweets", ["tweet_id" => "invalid-tweet-id"]);

		$response->assertStatus(400);

		$this->assertEquals("the tweet you want to retweet does not exist", $response->json("message"));
	}


	/** @test */
	public function the_undo_retweet_process_must_fail_if_no_tweet_found()
	{
		$user = User::factory()->activated()->create();

		Passport::actingAs($user);

		$response = $this->deleteJson("api/retweets/invalid-tweet-id");

		$response->assertStatus(400);

		$this->assertEquals("the retweet you want to undo does not exist", $response->json("message"));
	}

	/** @test */
	public function the_tweet_id_is_required_to_create_a_retweet()
	{
		$user = User::factory()->activated()->create();

		Passport::actingAs($user);

		$this->postJson("api/retweets", ["tweet_id" => null])
			->assertJsonValidationErrorFor("tweet_id");
	}
}
