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

        $this->assertArrayHasKey("email", $userResource);
        $this->assertArrayHasKey("is_activated", $userResource);

        $this->assertArrayNotHasKey("email_verified_at", $userResource);
        $this->assertArrayNotHasKey("country_code", $userResource);
        $this->assertArrayNotHasKey("phone", $userResource);
        $this->assertArrayNotHasKey("phone_validated", $userResource);
        $this->assertArrayNotHasKey("phone_verified_at", $userResource);
        $this->assertArrayNotHasKey("banner_id", $userResource);
        $this->assertArrayNotHasKey("image_id", $userResource);
        $this->assertArrayNotHasKey("country", $userResource);
        $this->assertArrayNotHasKey("gender", $userResource);
        $this->assertArrayNotHasKey("description", $userResource);
        $this->assertArrayNotHasKey("date_birth", $userResource);
        $this->assertArrayNotHasKey("deactivated_at", $userResource);
        $this->assertArrayNotHasKey("reactivated_at", $userResource);
        $this->assertArrayNotHasKey("updated_at", $userResource);
        $this->assertArrayNotHasKey("deleted_at", $userResource);
    }
}
