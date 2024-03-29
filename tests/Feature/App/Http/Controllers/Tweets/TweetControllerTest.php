<?php

namespace Tests\Feature\App\Http\Controllers\Tweets;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TweetCreated;
use App\Notifications\UserMentioned;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TweetControllerTest extends TestCase
{
	use RefreshDatabase;

	/** @test*/
	public function an_authenticated_user_can_tweet()
	{
		Notification::fake();

		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();
		$user2->follow($user->id);

		Passport::actingAs($user);

		$collectionName = "tweet_image";
		$media = $user->addMedia(storage_path('media-test/test_image.jpeg'))
			->preservingOriginal()
			->toMediaCollection($collectionName);

		$payload = [
			"body" => "My first tweet",
			"media" => [$media->uuid]
		];
		$response = $this->postJson("api/tweets", $payload)
			->assertSuccessful();

		$data = $response->json();
		$this->assertTweetResourceData($data);

		$this->assertDatabaseHas("tweets", [
			"user_id" => $user->id,
			"body" => $payload["body"]
		]);

		Notification::assertSentTo($user2, TweetCreated::class, function ($notification, $channels) use ($user2) {
			$this->assertTrue(!is_null($notification->tweet));
			$this->assertContains('broadcast', $channels);

			$tweetData = $notification->toArray($user2);
			$this->assertTweetResourceData($tweetData);

			$this->assertInstanceOf(BroadcastMessage::class, $notification->toBroadcast($user2));

			return true;
		});
	}

	/** @test*/
	public function a_guest_user_can_not_tweet()
	{
		$user = User::factory()->activated()->create();

		$payload = [
			"body" => "My first tweet"
		];
		$response = $this->postJson("api/tweets", $payload)
			->assertStatus(401);

		$this->assertEquals("Unauthenticated.", $response->json("message"));
		$this->assertDatabaseMissing("tweets", [
			"user_id" => $user->id,
			"body" => $payload["body"]
		]);
	}

	/** @test */
	public function a_user_can_see_a_specific_tweet()
	{
		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();
		$tweet = Tweet::factory()->create(["user_id" => $user2->id]);

		$response = $this->getJson("api/tweets/{$tweet->uuid}")
			->assertSuccessful();

		$data = $response->json();
		$this->assertTweetResourceData($data);
	}

	/** @test */
	public function the_show_tweet_process_must_fail_if_no_tweet_found()
	{
		$response = $this->getJson("api/tweets/invalid-uuid")
			->assertStatus(404);

		$this->assertEquals("the tweet does not exist", $response->json("message"));
	}

	/** @test*/
	public function an_authenticated_user_can_delete_tweets()
	{
		$user = User::factory()->activated()->create();
		$tweet = Tweet::factory()->create(["user_id" => $user->id]);

		$collectionName = "images";
		$media = $tweet->addMedia(storage_path('media-test/test_image.jpeg'))
			->preservingOriginal()
			->toMediaCollection($collectionName);

		$this->assertDatabaseHas("media", [
			"id" => $media->id,
			"name" => "test_image"
		]);

		Passport::actingAs($user);

		$this->assertDatabaseHas("tweets", [
			"user_id" => $user->id,
			"body" => $tweet->body,
			"deleted_at" => null
		]);

		$response = $this->deleteJson("api/tweets/{$tweet->uuid}");

		$this->assertEquals("tweet removed", $response->json("message"));

		$this->assertDatabaseHas("tweets", [
			"user_id" => $user->id,
			"body" => $tweet->body,
			"deleted_at" => $tweet->fresh()->deleted_at->format('Y-m-d H:i:s')
		]);

		$this->assertFalse(File::exists($media->getPath()));
		$this->assertDatabaseMissing("media", [
			"id" => $media->id,
			"name" => "test_image"
		]);
	}

	/** @test */
	public function a_removed_tweet_can_not_delete()
	{
		$user = User::factory()->activated()->create();
		$deletedTweet = Tweet::factory()->withSoftDelete()->create(["user_id" => $user->id]);

		Passport::actingAs($user);

		$response = $this->deleteJson("api/tweets/{$deletedTweet->uuid}");

		$response->assertStatus(404);
		$this->assertEquals("the tweet does not exist or has already been deleted", $response->json("message"));
	}

	/** @test */
	public function an_authenticated_user_can_not_delete_tweets_that_are_not_yours()
	{
		$user = User::factory()->activated()->create();
		$user2 = User::factory()->activated()->create();
		$tweet = Tweet::factory()->create(["user_id" => $user2->id]);

		Passport::actingAs($user);

		$response = $this->deleteJson("api/tweets/{$tweet->uuid}");

		$response->assertStatus(403);

		$this->assertEquals("you do not have permission to perform this action", $response->json("message"));
	}

	/** @test */
	public function a_new_tweet_can_have_media_files_related()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$collectionName = "tweet_image";
		$media = $user->addMedia(storage_path('media-test/test_image.jpeg'))
			->preservingOriginal()
			->toMediaCollection($collectionName);

		$this->assertDatabaseHas("media", [
			"model_id" => $user->id,
			"model_type" => User::class,
			"collection_name" => $collectionName,
			"file_name" => $media->file_name
		]);

		$payload = [
			"body" => "My first tweet with images",
			"media" => [$media->uuid]
		];
		$response = $this->postJson("api/tweets", $payload);

		$response->assertSuccessful();

		$this->assertDatabaseMissing("media", [
			"model_id" => $user->id,
			"model_type" => User::class,
			"collection_name" => $collectionName,
			"file_name" => $media->file_name
		]);

		$this->assertDatabaseHas("media", [
			"model_type" => Tweet::class,
			"collection_name" => "images",
			"file_name" => $media->file_name
		]);
	}

    /** @test */
    public function a_new_tweet_can_have_mentions_related()
    {
		Notification::fake();

        $user = User::factory()->activated()->create();
		Passport::actingAs($user);

        $userMentioned = User::factory()->activated()->create();

		$payload = [
			"body" => "My first tweet with images",
            "mentions" => [$userMentioned->uuid]
		];

		$response = $this->postJson("api/tweets", $payload);

        $data = $response->json();
		$this->assertTweetResourceData($data);

        $this->assertDatabaseHas("user_mentions", [
            "user_id" => $userMentioned->id
        ]);

        Notification::assertSentTo($userMentioned, UserMentioned::class, function ($notification, $channels) use ($userMentioned) {
			$this->assertTrue(!is_null($notification->tweet));
			$this->assertContains('broadcast', $channels);

			$tweetData = $notification->toArray($userMentioned);
			$this->assertTweetResourceData($tweetData["tweet"]);

			$this->assertInstanceOf(BroadcastMessage::class, $notification->toBroadcast($userMentioned));

			return true;
		});
    }

	/** @test */
	public function the_body_is_required()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);
		$this->postJson("api/tweets", ["body" => null])
			->assertJsonValidationErrorFor("body");
	}

	/** @test */
	public function the_body_should_not_have_more_than_280_characters()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);
		$body = "Lorem ipsum dolor, sit amet consectetur adipisicing elit. Esse harum id, nemo nostrum dicta voluptate deleniti suscipit alias dolores recusandae nisi aut ullam, consectetur minima, dignissimos quaerat magnam amet eligendi ipsum provident pariatur cumque consequuntur? Ratione, ipsa.";
		$this->postJson("api/tweets", ["body" => $body])
			->assertJsonValidationErrorFor("body");
	}

	/** @test */
	public function the_media_must_be_an_array()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);
		$this->postJson("api/tweets", ["body" => "My first tweet", "media" => null])
			->assertJsonValidationErrorFor("media");
	}


	/** @test */
	public function the_media_should_not_have_more_than_four_media_ids()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);
		$this->postJson("api/tweets", ["body" => "My first tweet", "media" => [1, 2, 3, 4, 5]])
			->assertJsonValidationErrorFor("media");
	}

	private function assertTweetResourceData(array $data)
	{
		$this->assertArrayHasKey("id", $data);
		$this->assertArrayHasKey("body", $data);
		$this->assertArrayHasKey("owner", $data);
		$this->assertArrayHasKey("mentions", $data);
		// $this->assertArrayHasKey("image", $data["owner"]);
		$this->assertArrayHasKey("images", $data);
		$this->assertArrayHasKey("replies_count", $data);
		$this->assertArrayHasKey("retweets_count", $data);
		$this->assertArrayHasKey("likes_count", $data);
	}
}
