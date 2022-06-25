<?php

namespace Tests\Feature\App\Http\Controllers\Media;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class MediaControllerTest extends TestCase
{

    use RefreshDatabase;

    /** @test */
    public function a_logged_user_can_upload_images_to_create_a_new_tweet()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        Storage::fake('media');

        $file = UploadedFile::fake()->image('test_image.jpg');
        $collectionName = "tweet_image";

        $response = $this->postJson('/api/media/upload', [
            "media" => $file,
            "media_category" => $collectionName
        ]);

        $response->assertSuccessful()
            ->assertJsonStructure(["media_id", "media_id_string", "media_url"]);

        $this->assertDatabaseHas("media", [
            "uuid" => $response->json("media_id"),
            "model_id" => $user->id,
            "model_type" => User::class,
            "collection_name" => $collectionName,
            "file_name" => $response->json("media_id_string")
        ]);
    }

    /** @test */
    public function a_logged_user_can_remove_media_file()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $collectionName = "twee_image";
        $media = $user->addMedia(storage_path('media-demo/test_image.jpeg'))
            ->preservingOriginal()
            ->toMediaCollection($collectionName);

        $this->assertTrue(File::exists($media->getPath()));
        $this->assertDatabaseHas("media", [
            "id" => $media->id,
            "model_id" => $user->id,
            "model_type" => User::class,
            "collection_name" => $collectionName,
            "file_name" => $media->file_name
        ]);

        $response = $this->deleteJson("/api/media/{$media->uuid}/remove");

        $response->assertSuccessful();
        $this->assertEquals("media removed successfully", $response->json("message"));

        $this->assertFalse(File::exists($media->getPath()));
        $this->assertDatabaseMissing("media", [
            "id" => $media->id,
            "model_id" => $user->id,
            "model_type" => User::class,
            "collection_name" => $collectionName,
            "file_name" => $media->file_name
        ]);
    }

    /** @test */
    public function a_media_file_cannot_be_deleted_if_it_does_not_exist()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $response = $this->deleteJson("/api/media/1/remove");

        $response->assertStatus(404);

        $this->assertEquals("the resource doesn't exist", $response->json("message"));
    }

    /** @test */
    public function the_media_is_required()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $this->postJson('/api/media/upload', ["media" => null])
            ->assertJsonValidationErrorFor("media");
    }


    /** @test */
    public function the_media_must_be_a_valid_media_file()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $this->postJson('/api/media/upload', ["media" => "invalid-media-file"])
            ->assertJsonValidationErrorFor("media");
    }


    /** @test */
    public function the_media_must_not_weigh_more_than_10_mb()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $file = UploadedFile::fake()->image('test_image.jpg')->size(12000);

        $this->postJson('/api/media/upload', ["media" => $file])
            ->assertJsonValidationErrorFor("media");
    }

    /** @test */
    public function the_media_category_must_be_a_valid_media_category()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $file = UploadedFile::fake()->image('test_image.jpg');

        $this->postJson('/api/media/upload', ["media" => $file, "media_category" => "invalid-media_category"])
            ->assertJsonValidationErrorFor("media_category");
    }


    /** @test */
    public function the_media_category_is_required()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $file = UploadedFile::fake()->image('test_image.jpg');

        $this->postJson('/api/media/upload', ["media" => $file, "media_category" => null])
            ->assertJsonValidationErrorFor("media_category");
    }
}
