<?php

namespace Tests\Feature\App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Auth\Concerns\UserAccount;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_login_with_their_identifier_and_password()
    {
        $user = User::factory()->withPhoneValidated()->create();

        $response = $this->postJson('api/auth/login', ["user_identifier" => $user->email, "password" => "password"])
                    ->assertSuccessful()
                    ->assertJsonStructure(["token", "user", "message"]);

        $this->assertEquals("successful login", $response->json("message"));

        $response = $this->postJson('api/auth/login', ["user_identifier" => $user->username, "password" => "password"])
                    ->assertSuccessful()
                    ->assertJsonStructure(["token", "user", "message"]);

        $this->assertEquals("successful login", $response->json("message"));

        $response = $this->postJson('api/auth/login', ["user_identifier" => $user->phone, "password" => "password"])
                    ->assertSuccessful()
                    ->assertJsonStructure(["token", "user", "message"]);

        $this->assertEquals("successful login", $response->json("message"));

        $this->assertArrayNotHasKey("email_verified_at", $response->json("user"));
        $this->assertArrayNotHasKey("country_code", $response->json("user"));
        $this->assertArrayNotHasKey("phone", $response->json("user"));
        $this->assertArrayNotHasKey("phone_validated", $response->json("user"));
        $this->assertArrayNotHasKey("phone_verified_at", $response->json("user"));
        $this->assertArrayNotHasKey("banner_id", $response->json("user"));
        $this->assertArrayNotHasKey("image_id", $response->json("user"));
        $this->assertArrayNotHasKey("country", $response->json("user"));
        $this->assertArrayNotHasKey("gender", $response->json("user"));
        $this->assertArrayNotHasKey("description", $response->json("user"));
        $this->assertArrayNotHasKey("date_birth", $response->json("user"));
        $this->assertArrayNotHasKey("deactivated_at", $response->json("user"));
        $this->assertArrayNotHasKey("reactivated_at", $response->json("user"));
        $this->assertArrayNotHasKey("updated_at", $response->json("user"));
        $this->assertArrayNotHasKey("deleted_at", $response->json("user"));
    }

    /** @test */
    public function the_login_process_must_be_fail_if_no_user_account_exists()
    {
        $response = $this->postJson('api/auth/login', ["user_identifier" => "invalid_identifier", "password" => "password_incorrect"])
            ->assertStatus(400)
            ->assertJsonStructure(["message"]);

        $this->assertEquals("login fail, we could not find your account", $response->json("message"));
    }


    /** @test */
    public function the_login_process_must_be_fail_if_the_password_is_wrong()
    {
        $user = User::factory()->withPhoneValidated()->create();

        $response = $this->postJson('api/auth/login', ["user_identifier" => $user->email, "password" => "password_incorrect"])
            ->assertStatus(400)
            ->assertJsonStructure(["message"]);

        $this->assertEquals("Wrong password", $response->json("message"));
    }

    /** @test */
    public function a_guest_user_can_reactivate_their_account_by_logging_in_if_it_has_not_been_deactivated_for_more_than_1_month()
    {
        $deactivationDate = now()->subDays(10);
        $user = User::factory()->create(["is_activated" => false, "deactivated_at" => $deactivationDate]);
        $reactivationDeadline = $user->deactivated_at->addDays(30);

        // Reactivate your account?
        // You deactivated your account on Jul 4, 2022.
        // On Aug 3, 2022, it will no longer be possible for you to restore your Twitter account if it was accidentally or wrongfully deactivated.
        // By clicking "Yes, reactivate", you will halt the deactivation process and reactivate your account.

        $response = $this->postJson('api/auth/login', ["user_identifier" => $user->email, "password" => "password"])
                ->assertJsonStructure(["message", "reactivation_deadline"]);

        $this->assertEquals("begin account activation process", $response->json("message"));
        $this->assertEquals($reactivationDeadline->format('Y-m-d'), $response->json("reactivation_deadline"));
    }


    /** @test */
    public function the_user_identifier_is_required()
    {
        $this->postJson('api/auth/login', ["user_identifier" => null])
                ->assertJsonValidationErrorFor('user_identifier');
    }


    /** @test */
    public function the_password_is_required()
    {
        $this->postJson('api/auth/login', ["user_identifier" => "test_identifier", "password" => null])
                ->assertJsonValidationErrorFor('password');
    }

    /** @test */
    public function the_login_controller_must_use_the_user_account_trait()
    {
        $this->assertClassUsesTrait(UserAccount::class, LoginController::class);
    }
}
