<?php

namespace Tests\Unit\App\Http\Resources;

use App\Http\Resources\MediaResource;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaResourceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_media_resources_must_have_the_necessary_keys()
    {
        $user = User::factory()->create();
        $tweet = Tweet::factory()->create(["user_id" => $user->id]);

        $media = $tweet->addMedia(storage_path('media-demo/test_image.jpeg'))
            ->preservingOriginal()
            ->toMediaCollection("images");

        $mediaResource = MediaResource::make($media->refresh())->resolve();

        $this->assertTrue(is_string($media->uuid));
        $this->assertEquals($media->uuid, $mediaResource["id"]);
        $this->assertEquals($media->getUrl(), $mediaResource["url"]);
        $this->assertEquals($media->getUrl(), $mediaResource["url"]);
        $this->assertEquals($media->created_at, $mediaResource["created_at"]);

        $this->assertArrayHasKey("conversions", $mediaResource);
    }
}
