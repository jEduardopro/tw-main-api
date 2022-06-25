<?php

namespace Tests\Unit\App\Http\Resources;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_resources_must_have_the_necessary_fields()
    {
        $user = User::factory()->create();
        $userResource = UserResource::make($user)->resolve();

        $this->assertTrue(is_string($user->uuid));
        $this->assertEquals($user->uuid, $userResource["id"]);
        $this->assertEquals($user->name, $userResource["name"]);
        $this->assertEquals($user->username, $userResource["username"]);
        $this->assertEquals($user->created_at, $userResource["created_at"]);
    }
}
