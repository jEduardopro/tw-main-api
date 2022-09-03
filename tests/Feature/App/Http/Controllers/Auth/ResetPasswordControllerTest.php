<?php

namespace Tests\Feature\App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Auth\Concerns\UserAccount;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Models\Flow;
use App\Notifications\ResetPassword;
use App\Utils\Task;
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

        $flow = Flow::factory()->create(["name" => Task::PASSWORD_RESET_BEGIN, "payload" => $this->getFlowPayload($user)]);

		$payload = [
			"description" => User::RESET_PASSWORD_BY_EMAIL,
            "flow_token" => $flow->token
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
	public function the_send_password_reset_process_must_be_fail_if_flow_token_no_exists()
	{
		$response = $this->postJson("api/auth/send-password-reset", [
                "description" => User::RESET_PASSWORD_BY_EMAIL,
                "flow_token" => "invalid-flow-token"
            ])
			->assertStatus(400)
			->assertJsonStructure(["message"]);

		$this->assertEquals("This flow was not found.", $response->json("message"));
	}

	/** @test */
	public function the_send_password_reset_process_must_be_fail_if_flow_is_expired()
	{
        $flow = Flow::factory()->create(["created_at" => now()->subMinutes(15)]);

		$response = $this->postJson("api/auth/send-password-reset", [
                "description" => User::RESET_PASSWORD_BY_EMAIL,
                "flow_token" => $flow->token
            ])
			->assertStatus(400)
			->assertJsonStructure(["message"]);

		$this->assertEquals("This flow has expired, please start again.", $response->json("message"));
	}

	/** @test */
	public function the_send_password_reset_process_must_be_fail_if_no_user_account_exists()
	{
        $user = User::factory()->withPhoneValidated()->create();

        $flow = Flow::factory()->create([
            "name" => Task::PASSWORD_RESET_BEGIN,
            "payload" => $this->getFlowPayload($user, ["reset_password_by_email" => "test_email@example.com"])
        ]);

		$response = $this->postJson("api/auth/send-password-reset", ["description" => User::RESET_PASSWORD_BY_EMAIL, "flow_token" => $flow->token])
			->assertStatus(400)
			->assertJsonStructure(["message"]);

		$this->assertEquals("We could not find your account", $response->json("message"));
	}

	/** @test */
	public function a_user_can_verify_their_password_reset_process()
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
        $flow = Flow::factory()->create([
            "name" => Task::PASSWORD_RESET_BEGIN,
            "payload" => $this->getFlowPayload($user)
        ]);

		$payload = [
			"flow_token" => $flow->token,
			"password" => "MyNewSecret55",
			"password_confirmation" => "MyNewSecret55"
		];
		$response = $this->postJson("api/auth/reset-password", $payload);

		$response->assertSuccessful();
		$this->assertEquals("successful password reset", $response->json("message"));

        $this->assertDatabaseMissing("flows", [
            "name" => $flow->name,
            "token" => $flow->token
        ]);
	}

    /** @test */
    public function the_generate_new_password_process_must_be_fail_if_flow_token_no_exists()
    {
        $payload = [
            "flow_token" => "invalid-flow-token",
            "password" => "MyNewSecret55",
            "password_confirmation" => "MyNewSecret55"
        ];

        $response = $this->postJson("api/auth/reset-password", $payload)
            ->assertStatus(400)
            ->assertJsonStructure(["message"]);

        $this->assertEquals("This flow was not found.", $response->json("message"));
    }

    /** @test */
    public function the_generate_new_password_process_must_be_fail_if_flow_is_expired()
    {
        $flow = Flow::factory()->create(["created_at" => now()->subMinutes(15)]);

        $payload = [
            "flow_token" => $flow->token,
            "password" => "MyNewSecret55",
            "password_confirmation" => "MyNewSecret55"
        ];

        $response = $this->postJson("api/auth/reset-password", $payload)
            ->assertStatus(400)
            ->assertJsonStructure(["message"]);

        $this->assertEquals("This flow has expired, please start again.", $response->json("message"));
    }

	/** @test */
	public function the_generate_new_password_process_must_be_fail_if_no_user_account_exists()
	{
        $user = User::factory()->withPhoneValidated()->create();

        $flow = Flow::factory()->create(["payload" => $this->getFlowPayload($user, ["username" => "invalid-username"])]);
		$payload = [
			"flow_token" => $flow->token,
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
    public function the_flow_token_is_required()
    {
        $this->postJson("api/auth/send-password-reset", ["description" => User::RESET_PASSWORD_BY_EMAIL, "email" => "test_email@example.com"])
            ->assertJsonValidationErrorFor("flow_token");
    }

    /** @test */
    public function the_flow_token_is_required_to_reset_password()
    {
        $this->postJson("api/auth/reset-password", ["password" => "Absecret55", "password_confirmation" => "Absecret55"])
            ->assertJsonValidationErrorFor("flow_token");
    }


	/** @test */
	public function the_password_is_required_to_reset_password()
	{
		$this->postJson("api/auth/reset-password", ["flow_token" => "test-flow-token", "password" => null])
			->assertJsonValidationErrorFor("password");
	}

	/** @test */
	public function the_password_must_be_have_at_least_eight_characters()
	{
		$payload = [
            "flow_token" => "test-flow-token",
			"password" => "123456"
		];
		$this->postJson('api/auth/reset-password', $payload)
			->assertJsonValidationErrorFor('password');
	}


	/** @test */
	public function the_password_must_be_have_at_least_one_uppercase_and_one_lowercase_letter()
	{
		$payload = [
			"flow_token" => "test-flow-token",
			"password" => "12345678"
		];
		$this->postJson('api/auth/reset-password', $payload)
			->assertJsonValidationErrorFor('password');
	}

	/** @test */
	public function the_password_must_be_have_at_least_one_number()
	{
		$payload = [
			"flow_token" => "test-flow-token",
			"password" => "Absecret"
		];
		$this->postJson('api/auth/reset-password', $payload)
			->assertJsonValidationErrorFor('password');
	}


	/** @test */
	public function the_password_must_be_confirmed()
	{
		$payload = [
            "flow_token" => "test-flow-token",
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

    private function getFlowPayload($accountInfo, $overrides = [])
    {
        return array_merge([
            "username" => $accountInfo->username,
            "reset_password_by_email" => $accountInfo->email,
            "reset_password_by_phone" => $accountInfo->phone,
        ], $overrides);
    }
}
