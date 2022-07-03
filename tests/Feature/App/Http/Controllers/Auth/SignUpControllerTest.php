<?php

namespace Tests\Feature\App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SignUpControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test*/
    public function a_user_can_finish_his_registration_with_email_and_password()
    {
        $user = User::factory()->withoutPassword()->create();

        $payload = [
            "description" => User::SIGN_UP_DESC_EMAIL,
            "email" => $user->email,
            "password" => "Absecret55"
        ];

        $this->assertDatabaseHas("users", [
            "email" => $user->email,
            "is_activated" => 0
        ]);

        $response = $this->postJson('/api/auth/signup', $payload);

        $response->assertSuccessful()
            ->assertJsonStructure([
                "token",
                "user"
            ]);

        $this->assertEquals("begin onboarding", $response->json("message"));

        $this->assertDatabaseHas("users", [
            "email" => $user->email,
            "is_activated" => 1
        ]);
    }

    /** @test*/
    public function a_user_can_finish_his_registration_with_phone_and_password()
    {
        $user = User::factory()->unverified()->withoutPassword()->withPhoneValidated()->create();

        $payload = [
            "description" => User::SIGN_UP_DESC_PHONE,
            "phone" => $user->phone_validated,
            "password" => "Absecret55"
        ];

        $this->assertDatabaseHas("users", [
            "phone" => $user->phone,
            "phone_validated" => $user->phone_validated,
            "is_activated" => 0
        ]);

        $response = $this->postJson('/api/auth/signup', $payload);

        $response->assertSuccessful()
            ->assertJsonStructure([
                "token",
                "user"
            ]);

        $this->assertEquals("begin onboarding", $response->json("message"));

        $this->assertDatabaseHas("users", [
            "phone" => $user->phone,
            "phone_validated" => $user->phone_validated,
            "is_activated" => 1
        ]);
    }

    /** @test */
    public function the_sign_up_process_must_be_fail_if_no_found_a_user()
    {
        $payload = [
            "description" => User::SIGN_UP_DESC_EMAIL,
            "email" => "test_email@example.com",
            "password" => "Absecret55"
        ];

        $response = $this->postJson('/api/auth/signup', $payload);

        $response->assertStatus(403);
    }

    /** @test */
    public function the_description_is_required()
    {
        $this->postJson('/api/auth/signup', ["description" => null])
            ->assertJsonValidationErrorFor("description");
    }


    /** @test */
    public function the_description_must_be_a_valid_description()
    {
        $this->postJson('/api/auth/signup', ["description" => "description_invalid"])
            ->assertJsonValidationErrorFor("description");
    }

    /** @test */
    public function the_email_must_be_required_when_the_sign_up_is_with_email()
    {
        $this->postJson('api/auth/signup', ["description" => User::SIGN_UP_DESC_EMAIL])
            ->assertJsonValidationErrorFor('email');
    }

    /** @test */
    public function the_email_must_be_a_valid_email_address()
    {
        $payload = [
            "email" => "invalidemail.com",
            "description" => User::SIGN_UP_DESC_EMAIL
        ];
        $this->postJson('api/auth/signup', $payload)
            ->assertJsonValidationErrorFor('email');
    }

    /** @test */
    public function the_phone_must_be_required_when_the_sign_up_is_with_phone()
    {
        $this->postJson('api/auth/signup', ["description" => User::SIGN_UP_DESC_PHONE])
            ->assertJsonValidationErrorFor('phone');
    }

    /** @test */
    public function the_phone_must_be_a_valid_phone_number()
    {
        $payload = [
            "phone" => "+1798456781",
            "description" => User::SIGN_UP_DESC_PHONE
        ];
        $this->postJson('api/auth/signup', $payload)
            ->assertJsonValidationErrorFor('phone');
    }

    /** @test */
    public function the_password_is_required()
    {
        $payload = [
            "email" => "test_email@example.com",
            "description" => User::SIGN_UP_DESC_EMAIL
        ];
        $this->postJson('api/auth/signup', $payload)
            ->assertJsonValidationErrorFor('password');
    }

    /** @test */
    public function the_password_must_be_have_at_least_8_characters()
    {
        $payload = [
            "email" => "test_email@example.com",
            "description" => User::SIGN_UP_DESC_EMAIL,
            "password" => "123456"
        ];
        $this->postJson('api/auth/signup', $payload)
            ->assertJsonValidationErrorFor('password');
    }


    /** @test */
    public function the_password_must_be_have_at_least_one_uppercase_and_one_lowercase_letter()
    {
        $payload = [
            "email" => "test_email@example.com",
            "description" => User::SIGN_UP_DESC_EMAIL,
            "password" => "12345678"
        ];
        $this->postJson('api/auth/signup', $payload)
            ->assertJsonValidationErrorFor('password');
    }

    /** @test */
    public function the_password_must_be_have_at_least_one_number()
    {
        $payload = [
            "email" => "test_email@example.com",
            "description" => User::SIGN_UP_DESC_EMAIL,
            "password" => "Absecret"
        ];
        $this->postJson('api/auth/signup', $payload)
            ->assertJsonValidationErrorFor('password');
    }
}
