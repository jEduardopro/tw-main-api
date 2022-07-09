<?php

namespace Tests\Feature\App\Http\Controllers\Users;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UserTimelineControllerTest extends TestCase
{
	use RefreshDatabase;

	/** @test */
	public function an_authenticated_user_can_see_the_timeline_of_their_tweets()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		Tweet::factory()->create(["user_id" => $user->id, "created_at" => now()->subMinutes(5)]);
		$tweet2 = Tweet::factory()->create(["user_id" => $user->id]);

		$response = $this->getJson("api/users/{$user->uuid}/timeline");

		$response->assertSuccessful()
			->assertJsonStructure(["data", "meta", "links"]);


		$this->assertEquals($tweet2->body, $response->json("data.0.body"));
		$this->assertArrayHasKey("images", $response->json("data.0"));
		$this->assertArrayHasKey("owner", $response->json("data.0"));
		$this->assertArrayHasKey("image", $response->json("data.0.owner"));
		$this->assertArrayHasKey("retweets_count", $response->json("data.0"));
		$this->assertArrayHasKey("replies_count", $response->json("data.0"));
		$this->assertArrayHasKey("likes_count", $response->json("data.0"));
	}

	/** @test */
	public function a_guest_user_can_see_the_timeline_of_their_tweets()
	{
		$user = User::factory()->activated()->create();

		Tweet::factory()->create(["user_id" => $user->id, "created_at" => now()->subMinutes(5)]);
		$tweet2 = Tweet::factory()->create(["user_id" => $user->id]);

		$response = $this->getJson("api/users/{$user->uuid}/timeline");

		$response->assertSuccessful()
			->assertJsonStructure(["data", "meta", "links"]);

		$this->assertEquals($tweet2->body, $response->json("data.0.body"));
		$this->assertArrayHasKey("images", $response->json("data.0"));
		$this->assertArrayHasKey("owner", $response->json("data.0"));
		$this->assertArrayHasKey("image", $response->json("data.0.owner"));
        $this->assertArrayHasKey("retweets_count", $response->json("data.0"));
        $this->assertArrayHasKey("replies_count", $response->json("data.0"));
        $this->assertArrayHasKey("likes_count", $response->json("data.0"));
	}

	/** @test */
	public function a_user_can_see_their_tweets_and_retweets_on_their_timeline_sorted_by_creation_date()
	{
		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();

		$tweetToRetweet = Tweet::factory()->create(["user_id" => $user2->id, "created_at" => now()->subMinutes(15)]);
		Tweet::factory()->count(5)->create(["user_id" => $user->id, "created_at" => now()->subMinutes(5)]);

		$user->retweet($tweetToRetweet->id);

		$lastTweet = Tweet::factory()->create(["user_id" => $user->id, "created_at" => now()->addMinutes(10)]);


		$response = $this->getJson("api/users/{$user->uuid}/timeline");


		$response->assertSuccessful()
			->assertJsonStructure(["data", "meta", "links"]);

        $this->assertArrayHasKey("retweets_count", $response->json("data.0"));
        $this->assertArrayHasKey("replies_count", $response->json("data.0"));
        $this->assertArrayHasKey("likes_count", $response->json("data.0"));
		$this->assertEquals($lastTweet->body, $response->json("data.0.body"));
		$this->assertEquals($tweetToRetweet->uuid, $response->json("data.1.id"));
		$this->assertEquals(1, $response->json("data.1.retweets_count"));
	}

	/** @test */
	public function the_timeline_of_a_user_must_return_an_error_if_the_user_not_exists()
	{
		$user = User::factory()->withSoftDelete()->create();
		$user2 = User::factory()->activated()->create();
		Passport::actingAs($user2);

		$response = $this->getJson("api/users/{$user->uuid}/timeline");

		$response->assertStatus(404);

		$this->assertEquals("the timeline of tweets is not available for this account", $response->json("message"));
	}

	/** @test */
	public function the_timeline_of_a_user_must_return_an_error_if_the_user_account_is_deactivate()
	{
		$user = User::factory()->create();
		$user2 = User::factory()->activated()->create();
		Passport::actingAs($user2);

		$response = $this->getJson("api/users/{$user->uuid}/timeline");

		$response->assertStatus(404);

		$this->assertEquals("the timeline of tweets is not available for this account", $response->json("message"));
	}
}
