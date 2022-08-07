<?php

namespace Tests\Feature\App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Auth\Concerns\UserAccount;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ResetPasswordControllerTest extends TestCase
{

	use RefreshDatabase;

	/** @test */
	public function a_user_can_reset_their_password_by_emai()
	{
		Notification::fake();

		$user = User::factory()->withPhoneValidated()->create();

		$payload = [
			"description" => User::RESET_PASSWORD_BY_EMAIL,
			"email" => $user->email
		];

		$this->assertDatabaseCount("password_resets", 0);
		$response = $this->postJson("api/auth/send-password-reset", $payload);

		$response->assertSuccessful();

		$this->assertEquals("The verification code was sent", $response->json("message"));

		$this->assertDatabaseHas("password_resets", [
			"email" => $user->email
		]);
		$this->assertDatabaseCount("password_resets", 1);

		Notification::assertSentTo($user, ResetPassword::class, function ($notification, $channels) use ($user) {
			$mail = $notification->toMail($user);
			$this->assertInstanceOf(MailMessage::class, $mail);
			$this->assertContains("mail", $channels);
			$this->assertTrue(!is_null($notification->token));
			$this->assertArrayHasKey("token", $mail->viewData);
			return true;
		});
	}

	/** @test */
	public function the_send_password_reset_process_must_be_fail_if_no_user_account_exists()
	{
		$response = $this->postJson("api/auth/send-password-reset", ["description" => User::RESET_PASSWORD_BY_EMAIL, "email" => "test_email@example.com"])
			->assertStatus(400)
			->assertJsonStructure(["message"]);

		$this->assertEquals("We could not find your account", $response->json("message"));
	}

	/** @test */
	public function a_use_can_verify_their_password_reset_process()
	{
		$user = User::factory()->create();
		$token = $user->generateCode();

		$passwordResetData = ["email" => $user->email, "token" => $token, "created_at" => now()];
		DB::table("password_resets")->insert($passwordResetData);

		$response = $this->postJson("api/auth/password-verify-code", ["token" => $token]);

		$response->assertSuccessful();
		$this->assertEquals("password reset request verified successfully", $response->json("message"));
		$this->assertDatabaseMissing("password_resets", $passwordResetData);
	}

	/** @test */
	public function a_user_can_generate_new_password()
	{
		$user = User::factory()->create();

		$payload = [
			"email" => $user->email,
			"password" => "MyNewSecret55",
			"password_confirmation" => "MyNewSecret55"
		];
		$response = $this->postJson("api/auth/reset-password", $payload);

		$response->assertSuccessful();
		$this->assertEquals("successful password reset", $response->json("message"));
	}

	/** @test */
	public function the_generate_new_password_process_must_be_fail_if_no_user_account_exists()
	{
		$payload = [
			"email" => "test_email@example.com",
			"password" => "MyNewSecret55",
			"password_confirmation" => "MyNewSecret55"
		];
		$response = $this->postJson("api/auth/reset-password", $payload)
			->assertStatus(400)
			->assertJsonStructure(["message"]);

		$this->assertEquals("We could not find your account", $response->json("message"));
	}

	/** @test */
	public function the_verify_process_must_be_fail_if_no_password_resets_token()
	{
		$user = User::factory()->create();
		$token = $user->generateCode();

		$response = $this->postJson("api/auth/password-verify-code", ["token" => $token])
                ->assertStatus(400);

        $this->assertEquals("The code you entered is incorrect", $response->json("message"));
	}

	/** @test */
	public function the_verification_code_must_be_valid()
	{
		$user = User::factory()->create();
		$token = $user->generateCode();

		$passwordResetData = ["email" => $user->email, "token" => $token, "created_at" => now()->subMinutes(10)];
		DB::table("password_resets")->insert($passwordResetData);

		$response = $this->postJson("api/auth/password-verify-code", ["token" => $token])
                ->assertStatus(400);

        $this->assertEquals("The code you entered is expired", $response->json("message"));
	}

	/** @test */
	public function the_verification_code_is_required()
	{
		$this->postJson("api/auth/password-verify-code", ["token" => null])
			->assertJsonValidationErrorFor("token")
			->assertJsonValidationErrors([
				"token" => ["The token field is required."]
			]);
	}

	/** @test */
	public function the_description_is_required()
	{
		$this->postJson("api/auth/send-password-reset", ["description" => null])
			->assertJsonValidationErrorFor("description");
	}

	/** @test */
	public function the_description_is_must_be_a_valid_description()
	{
		$this->postJson("api/auth/send-password-reset", ["description" => "invalid-description"])
			->assertJsonValidationErrorFor("description");
	}


	/** @test */
	public function the_email_is_required_when_a_user_reset_password_by_email()
	{
		$this->postJson("api/auth/send-password-reset", ["description" => User::RESET_PASSWORD_BY_EMAIL])
			->assertJsonValidationErrorFor("email");
	}

	/** @test */
	public function the_email_must_be_a_valid_email_address()
	{
		$this->postJson("api/auth/send-password-reset", ["description" => User::RESET_PASSWORD_BY_EMAIL, "email" => "invalid-email"])
			->assertJsonValidationErrorFor("email");
	}

	/** @test */
	public function the_email_is_required_to_reset_password()
	{
		$payload = [
			"email" => null,
			"password" => "MyNewSecret55",
			"password_confirmation" => "MyNewSecret55"
		];
		$this->postJson("api/auth/reset-password", $payload)
			->assertJsonValidationErrorFor("email");
	}


	/** @test */
	public function the_email_must_be_a_valid_email_address_to_reset_password()
	{
		$payload = [
			"email" => "invalid-email",
			"password" => "MyNewSecret55",
			"password_confirmation" => "MyNewSecret55"
		];
		$this->postJson("api/auth/reset-password", $payload)
			->assertJsonValidationErrorFor("email");
	}


	/** @test */
	public function the_password_is_required_to_reset_password()
	{
		$this->postJson("api/auth/reset-password", ["email" => "test_email@example.com", "password" => null])
			->assertJsonValidationErrorFor("password");
	}

	/** @test */
	public function the_password_must_be_have_at_least_eight_characters()
	{
		$payload = [
			"email" => "test_email@example.com",
			"password" => "123456"
		];
		$this->postJson('api/auth/reset-password', $payload)
			->assertJsonValidationErrorFor('password');
	}


	/** @test */
	public function the_password_must_be_have_at_least_one_uppercase_and_one_lowercase_letter()
	{
		$payload = [
			"email" => "test_email@example.com",
			"password" => "12345678"
		];
		$this->postJson('api/auth/reset-password', $payload)
			->assertJsonValidationErrorFor('password');
	}

	/** @test */
	public function the_password_must_be_have_at_least_one_number()
	{
		$payload = [
			"email" => "test_email@example.com",
			"password" => "Absecret"
		];
		$this->postJson('api/auth/reset-password', $payload)
			->assertJsonValidationErrorFor('password');
	}


	/** @test */
	public function the_password_must_be_confirmed()
	{
		$payload = [
			"email" => "test_email@example.com",
			"password" => "Absecret55"
		];
		$this->postJson('api/auth/reset-password', $payload)
			->assertJsonValidationErrorFor('password');
	}

	/** @test */
	public function the_reset_password_controller_must_use_the_user_account_trait()
	{
		$this->assertClassUsesTrait(UserAccount::class, ResetPasswordController::class);
	}
}
