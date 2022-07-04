<?php

namespace Tests\Feature\App\Http\Controllers\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AccountReactivationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_user_can_reactivate_their_account()
    {
        $user = User::factory()->deactivated()->create();

        $payload = [
            "user_identifier" => $user->email,
            "password" => "password"
        ];

        $response = $this->postJson("api/account/reactivation", $payload);

        $response->assertSuccessful()
                ->assertJsonStructure(["token", "user", "message", "reactivated_at"]);

        $this->assertEquals("successfully account reactivation", $response->json("message"));

        $this->assertDatabaseHas("users", [
            "id" => $user->id,
            "is_activated" => 1,
            "deactivated_at" => null,
            "reactivated_at" => $response->json("reactivated_at")
        ]);
    }

    /** @test */
    public function the_reactivation_process_should_fail_if_a_user_account_is_not_found()
    {
        $payload = [
            "user_identifier" => "invalid-identifier",
            "password" => "password"
        ];

        $response = $this->postJson("api/account/reactivation", $payload)->assertStatus(400);

        $this->assertEquals("reactivation fail, we could not find your account", $response->json("message"));
    }


    /** @test */
    public function the_reactivation_process_should_fail_if_the_credentials_are_invalid()
    {
        $user = User::factory()->deactivated()->create();

        $payload = [
            "user_identifier" => $user->email,
            "password" => "wrong-secret-pass"
        ];

        $response = $this->postJson("api/account/reactivation", $payload)->assertStatus(400);

        $this->assertEquals("the credentials are invalid", $response->json("message"));
    }
}
