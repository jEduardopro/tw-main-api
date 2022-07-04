<?php

namespace Tests\Unit\App\Http\Resources;

use App\Http\Resources\ProfileResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileResourceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_profile_resources_must_have_the_necessary_keys()
    {
        $user = User::factory()->activated()->create();

        $profileResource = ProfileResource::make($user)->resolve();


        $this->assertEquals($user->uuid, $profileResource["id"]);
        $this->assertEquals($user->name, $profileResource["name"]);
        $this->assertEquals($user->username, $profileResource["username"]);
        $this->assertEquals($user->description, $profileResource["description"]);
        $this->assertEquals($user->getReadableJoinedDate(), $profileResource["readable_joined_date"]);
    }

    /** @test */
    public function a_profile_resources_must_have_the_key_of_image_when_its_profile_image_relation_is_loaded()
    {
        $user = User::factory()->activated()->create();

        $profileResource = ProfileResource::make($user)->resolve();

        $this->assertArrayNotHasKey("image", $profileResource);

        $user->load('profileImage');

        $profileResource = ProfileResource::make($user)->resolve();

        $this->assertArrayHasKey("image", $profileResource);
    }


    /** @test */
    public function a_profile_resources_must_have_the_key_of_banner_when_its_profile_banner_relation_is_loaded()
    {
        $user = User::factory()->activated()->create();

        $profileResource = ProfileResource::make($user)->resolve();

        $this->assertArrayNotHasKey("banner", $profileResource);

        $user->load('profileBanner');

        $profileResource = ProfileResource::make($user)->resolve();

        $this->assertArrayHasKey("banner", $profileResource);
    }

    /** @test */
    public function a_profile_resources_must_have_the_key_of_following_count_when_its_following_count_relation_is_loaded()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();

        $user->follow($user2->id);

        $profileResource = ProfileResource::make($user)->resolve();

        $this->assertArrayNotHasKey("following_count", $profileResource);

        $user->loadCount('following');

        $profileResource = ProfileResource::make($user)->resolve();

        $this->assertArrayHasKey("following_count", $profileResource);
    }


    /** @test */
    public function a_profile_resources_must_have_the_key_of_followers_count_key_when_its_followers_count_relation_is_loaded()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();

        $user2->follow($user->id);

        $profileResource = ProfileResource::make($user)->resolve();

        $this->assertArrayNotHasKey("followers_count", $profileResource);

        $user->loadCount('followers');

        $profileResource = ProfileResource::make($user)->resolve();

        $this->assertArrayHasKey("followers_count", $profileResource);
    }
}
