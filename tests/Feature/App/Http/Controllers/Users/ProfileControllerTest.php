<?php

namespace Tests\Feature\App\Http\Controllers\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_logged_user_can_see_your_profile()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $response = $this->getJson("api/users/{$user->username}/profile");

        $response->assertSuccessful();

        $this->assertEquals($user->uuid, $response->json("data.id"));
        $this->assertEquals($user->username, $response->json("data.username"));
    }

    /** @test */
    public function a_logged_user_can_see_other_user_profiles()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $user2 = User::factory()->activated()->create();

        $response = $this->getJson("api/users/{$user2->username}/profile");

        $response->assertSuccessful();

        $this->assertEquals($user2->uuid, $response->json("data.id"));
        $this->assertEquals($user2->username, $response->json("data.username"));
    }

    /** @test */
    public function a_guest_user_can_see_your_profile()
    {
        $user = User::factory()->activated()->create();

        $response = $this->getJson("api/users/{$user->username}/profile");

        $response->assertSuccessful();

        $this->assertEquals($user->uuid, $response->json("data.id"));
        $this->assertEquals($user->username, $response->json("data.username"));
    }


    /** @test */
    public function a_guest_user_can_see_other_user_profiles()
    {
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create();

        $response = $this->getJson("api/users/{$user2->username}/profile");

        $response->assertSuccessful();

        $this->assertEquals($user2->uuid, $response->json("data.id"));
        $this->assertEquals($user2->username, $response->json("data.username"));
    }


    /** @test */
    public function the_profile_request_must_fail_if_user_account_not_found()
    {
        $response = $this->getJson("api/users/invalid-username/profile");

        $response->assertStatus(400);

        $this->assertEquals("This account doesn't exist", $response->json("message"));
    }


    /** @test */
    public function the_profile_request_must_fail_if_user_account_is_deactivate()
    {
        $user = User::factory()->create();

        $response = $this->getJson("api/users/{$user->username}/profile");

        $response->assertStatus(400);

        $this->assertEquals("Account suspended", $response->json("message"));
    }
}
