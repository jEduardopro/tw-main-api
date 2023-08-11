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

        $this->assertArrayNotHasKey("email_verified_at", $response->json("user"));
        $this->assertArrayNotHasKey("country_code", $response->json("user"));
        $this->assertArrayNotHasKey("phone_validated", $response->json("user"));
        $this->assertArrayNotHasKey("phone_verified_at", $response->json("user"));
        $this->assertArrayNotHasKey("banner_id", $response->json("user"));
        $this->assertArrayNotHasKey("image_id", $response->json("user"));
        $this->assertArrayNotHasKey("deactivated_at", $response->json("user"));
        $this->assertArrayNotHasKey("reactivated_at", $response->json("user"));
        $this->assertArrayNotHasKey("updated_at", $response->json("user"));
        $this->assertArrayNotHasKey("deleted_at", $response->json("user"));

        $this->assertArrayHasKey("image", $response->json("user"));
        $this->assertArrayHasKey("phone", $response->json("user"));
        $this->assertArrayHasKey("country", $response->json("user"));
        $this->assertArrayHasKey("gender", $response->json("user"));
        $this->assertArrayHasKey("description", $response->json("user"));
        $this->assertArrayHasKey("date_birth", $response->json("user"));


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


    /** @test */
    public function the_reactivation_process_should_fail_if_the_user_account_is_already_activated()
    {
        $user = User::factory()->reactivated()->create();

        $payload = [
            "user_identifier" => $user->email,
            "password" => "password"
        ];

        $response = $this->postJson("api/account/reactivation", $payload)->assertStatus(400);

        $this->assertEquals("this account is already activated", $response->json("message"));
    }

    /** @test */
    public function the_user_identifier_is_required()
    {
        $this->postJson("api/account/reactivation", ["user_identifier" => null])
                    ->assertJsonValidationErrorFor("user_identifier");
    }


    /** @test */
    public function the_password_is_required()
    {
        $this->postJson("api/account/reactivation", ["user_identifier" => "test-identifier", "password" => null])
                    ->assertJsonValidationErrorFor("password");
    }
}
