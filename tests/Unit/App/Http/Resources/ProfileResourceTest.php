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
}
