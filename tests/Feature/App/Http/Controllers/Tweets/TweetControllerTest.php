<?php

namespace Tests\Feature\App\Http\Controllers\Tweets;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TweetControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test*/
    public function a_logged_user_can_tweet()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $payload = [
            "body" => "My first tweet"
        ];
        $response = $this->postJson("api/tweets", $payload);

        $this->assertEquals("successful tweet", $response->json("message"));
        $this->assertDatabaseHas("tweets", [
            "user_id" => $user->id,
            "body" => $payload["body"]
        ]);
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

    /** @test*/
    public function a_logged_user_can_delete_tweets()
    {
        $user = User::factory()->activated()->create();
        $tweet = Tweet::factory()->create(["user_id" => $user->id]);

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
    public function a_new_tweet_can_have_media_files_related()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $collectionName = "twee_image";
        $media = $user->addMedia(storage_path('media-demo/test_image.jpeg'))
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
        $this->postJson("api/tweets", ["body" => "My first tweet", "media" => [1,2,3,4,5]])
            ->assertJsonValidationErrorFor("media");
    }
}
