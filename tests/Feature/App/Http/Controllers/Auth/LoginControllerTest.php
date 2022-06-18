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
