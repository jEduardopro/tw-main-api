<?php

namespace Tests\Feature\App\Http\Controllers\Account;

use App\Models\Flow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AccountPasswordControllerTest extends TestCase
{
	use RefreshDatabase;

	/** @test */
	public function an_authenticated_user_can_update_their_password()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$user->createToken('ttoken')->accessToken;

		$this->assertEquals(1, $user->tokens->count());
		$this->assertDatabaseHas("oauth_access_tokens", [
			"user_id" => $user->id,
			"name" => "ttoken",
			"revoked" => 0
		]);

		$newPassword = "NewSecret1";
		$payload = [
			"current_password" => "password",
			"new_password" => $newPassword,
			"new_password_confirmation" => $newPassword
		];
		$response = $this->putJson("api/account/password", $payload);

		$response->assertSuccessful();

		$this->assertEquals("password updated", $response->json("message"));

        $flow = Flow::factory()->create();
		$loginResponse = $this->postJson("api/auth/login", [
			"user_identifier" => $user->email,
			"password" => "password",
            "flow_token" => $flow->token
		])->assertStatus(400);

		$this->assertEquals("Wrong password", $loginResponse->json("message"));

		$this->assertDatabaseHas("oauth_access_tokens", [
			"user_id" => $user->id,
			"name" => "ttoken",
			"revoked" => 1
		]);

		$loginResponse = $this->postJson("api/auth/login", [
			"user_identifier" => $user->email,
			"password" => $newPassword,
            "flow_token" => $flow->token
		])->assertStatus(200);

		$this->assertEquals("successful login", $loginResponse->json("message"));
	}

	/** @test */
	public function the_update_password_process_must_fail_if_the_current_password_is_wrong()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$newPassword = "NewSecret1";
		$payload = [
			"current_password" => "wrong-current-password",
			"new_password" => $newPassword,
			"new_password_confirmation" => $newPassword
		];
		$this->putJson("api/account/password", $payload)
			->assertStatus(422)
			->assertJsonValidationErrorFor("current_password");
	}

	/** @test */
	public function the_current_password_is_required()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$newPassword = "NewSecret1";
		$payload = [
			"current_password" => null,
			"new_password" => $newPassword,
			"new_password_confirmation" => $newPassword
		];

		$response = $this->putJson("api/account/password", $payload)
			->assertJsonValidationErrorFor("current_password");

		$this->assertEquals("The current password field is required.", $response->json("errors.current_password.0"));
	}


	/** @test */
	public function the_new_password_is_required()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$newPassword = "NewSecret1";
		$payload = [
			"current_password" => "password",
			"new_password" => null,
			"new_password_confirmation" => $newPassword
		];

		$this->putJson("api/account/password", $payload)
			->assertJsonValidationErrorFor("new_password");
	}

	/** @test */
	public function the_new_password_must_be_have_at_least_8_characters()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$newPassword = "NewSecret1";
		$payload = [
			"current_password" => "password",
			"new_password" => "secret",
			"new_password_confirmation" => $newPassword
		];

		$this->putJson("api/account/password", $payload)
			->assertJsonValidationErrorFor("new_password");
	}


	/** @test */
	public function the_new_password_must_be_have_at_least_one_uppercase_and_one_lowercase_letter()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$newPassword = "NewSecret1";
		$payload = [
			"current_password" => "password",
			"new_password" => "mynewsecret",
			"new_password_confirmation" => $newPassword
		];

		$this->putJson("api/account/password", $payload)
			->assertJsonValidationErrorFor("new_password");
	}

	/** @test */
	public function the_new_password_must_be_have_at_least_one_number()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$newPassword = "NewSecret1";
		$payload = [
			"current_password" => "password",
			"new_password" => "MyNewSecret",
			"new_password_confirmation" => $newPassword
		];

		$this->putJson("api/account/password", $payload)
			->assertJsonValidationErrorFor("new_password");
	}


	/** @test */
	public function the_new_password_must_be_confirmed()
	{
		$user = User::factory()->activated()->create();
		Passport::actingAs($user);

		$newPassword = "NewSecret1";
		$payload = [
			"current_password" => "password",
			"new_password" => $newPassword,
			"new_password_confirmation" => null
		];

		$this->putJson("api/account/password", $payload)
			->assertJsonValidationErrorFor("new_password");
	}
}
