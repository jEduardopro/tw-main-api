<?php

namespace Tests\Feature\App\Http\Controllers\Account;

use App\Models\User;
use App\Notifications\VerifyNewEmailAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AccountInformationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_authenticated_user_can_update_their_username()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $username = "testusername" . time();
        $response = $this->putJson("api/account/information/update-username", ["username" => $username]);

        $response->assertSuccessful();

        $this->assertEquals("username updated", $response->json("message"));
        $this->assertDatabaseHas("users", [
            "id" => $user->id,
            "username" => $username
        ]);
    }


    /** @test */
    public function an_authenticated_user_can_add_a_new_email_address_to_verify()
    {
        Notification::fake();

        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $email = $this->faker->email;
        $response = $this->postJson("api/account/information/verify-new-email", ["email" => $email]);

        $response->assertSuccessful();

        $this->assertEquals("The verification code was sent", $response->json("message"));

        $this->assertDatabaseHas("user_verifications", ["user_id" => $user->id]);
        $this->assertDatabaseCount("user_verifications", 1);

        Notification::assertSentTo($user, VerifyNewEmailAddress::class, function ($notification, $channels) use ($user) {
            $mailInfo = $notification->toMail($user);

            $this->assertInstanceOf(MailMessage::class, $mailInfo);
            $this->assertTrue(str_contains((string) $mailInfo->subject, "is your twitter clone verification code"));
            $this->assertEquals("mail.account.information.verify-new-email-address", $mailInfo->markdown);
            $this->assertArrayHasKey("code", $mailInfo->viewData);
            $this->assertTrue(!is_null($notification->email));
            $this->assertTrue(!is_null($notification->code));
            $this->assertContains("mail", $channels);

            return true;
        });
    }


    /** @test */
    public function an_authenticated_user_can_resend_the_verification_code_to_verify_their_new_email_address()
    {
        Notification::fake();

        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $email = $this->faker->email;
        $response = $this->postJson("api/account/information/resend-new-email", ["email" => $email]);

        $response->assertSuccessful();

        $this->assertEquals("The verification code was sent", $response->json("message"));

        $this->assertDatabaseHas("user_verifications", ["user_id" => $user->id]);
        $this->assertDatabaseCount("user_verifications", 1);

        Notification::assertSentTo($user, VerifyNewEmailAddress::class);
    }

    /** @test */
    public function an_authenticated_user_can_verify_their_new_email_address_to_update()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $code = $user->createVerificationCodeForUser($user->id);
        $this->assertDatabaseCount("user_verifications", 1);
        $this->assertDatabaseHas("user_verifications", ["user_id" => $user->id]);

        $email = $this->faker->email;
        $response = $this->putJson("api/account/information/update-email", ["email" => $email, "code" => $code]);

        $response->assertSuccessful()
            ->assertJsonStructure(["verified_at", "message"]);

        $this->assertEquals("Email address updated", $response->json("message"));

        $this->assertDatabaseMissing("user_verifications", ["user_id" => $user->id]);
        $this->assertDatabaseCount("user_verifications", 0);

        $this->assertDatabaseHas("users", [
            "id" => $user->id,
            "email" => $email,
            "email_verified_at" => $response->json("verified_at")
        ]);
    }

    /** @test */
    public function the_process_to_verify_code_and_update_email_address_must_fail_if_the_code_no_found()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $code = "invalid-code";

        $email = $this->faker->email;
        $response = $this->putJson("api/account/information/update-email", ["email" => $email, "code" => $code]);

        $response->assertStatus(400)
            ->assertJsonStructure(["message"]);

        $this->assertEquals("The code you entered is incorrect", $response->json("message"));
    }

    /** @test */
    public function the_process_to_verify_code_and_update_email_address_must_fail_if_the_code_is_expired()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $code = $user->generateCode();
        DB::table('user_verifications')->insert(['user_id' => $user->id, 'code' => $code, 'created_at' => now()->subMinutes(15)]);

        $email = $this->faker->email;
        $response = $this->putJson("api/account/information/update-email", ["email" => $email, "code" => $code]);

        $response->assertStatus(400);

        $this->assertEquals("The code you entered is expired", $response->json("message"));
    }

    /** @test */
    public function the_username_is_required_to_update_it()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $this->putJson("api/account/information/update-username", ["username" => null])
            ->assertJsonValidationErrorFor("username");
    }


    /** @test */
    public function the_username_must_be_unique_to_update_it()
    {
        $usernameAlreadyTaken = "test_username";
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create(["username" => $usernameAlreadyTaken]);
        Passport::actingAs($user);

        $this->putJson("api/account/information/update-username", ["username" => $usernameAlreadyTaken])
            ->assertJsonValidationErrorFor("username");
    }

    /** @test */
    public function the_username_must_be_a_valid_username()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $this->putJson("api/account/information/update-username", ["username" => "invalid username"])
            ->assertJsonValidationErrorFor("username");
    }

    /** @test */
    public function the_email_is_required_to_verify_it()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $this->postJson("api/account/information/verify-new-email", ["email" => null])
            ->assertJsonValidationErrorFor("email");
    }


    /** @test */
    public function the_email_must_be_unique_to_verify_it()
    {
        $emailAlreadyTaken = "email_test@example.com";
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create(["email" => $emailAlreadyTaken]);
        Passport::actingAs($user);

        $this->postJson("api/account/information/verify-new-email", ["email" => $emailAlreadyTaken])
            ->assertJsonValidationErrorFor("email");
    }


    /** @test */
    public function the_email_must_be_a_valid_email_address_to_verify_it()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $this->postJson("api/account/information/verify-new-email", ["email" => "invalid-email-address"])
            ->assertJsonValidationErrorFor("email");
    }


    /** @test */
    public function the_email_is_required_to_update_it()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $this->putJson("api/account/information/update-email", ["email" => null])
            ->assertJsonValidationErrorFor("email");
    }


    /** @test */
    public function the_email_must_be_unique_to_update_it()
    {
        $emailAlreadyTaken = "email_test@example.com";
        $user = User::factory()->activated()->create();
        $user2 = User::factory()->activated()->create(["email" => $emailAlreadyTaken]);
        Passport::actingAs($user);

        $this->putJson("api/account/information/update-email", ["email" => $emailAlreadyTaken])
            ->assertJsonValidationErrorFor("email");
    }


    /** @test */
    public function the_email_must_be_a_valid_email_address_to_update_it()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $this->putJson("api/account/information/update-email", ["email" => "invalid-email-address"])
            ->assertJsonValidationErrorFor("email");
    }

    /** @test */
    public function the_code_is_required_to_update_it()
    {
        $user = User::factory()->activated()->create();
        Passport::actingAs($user);

        $this->putJson("api/account/information/update-email", ["email" => "test_email@example.com", "code" => null])
            ->assertJsonValidationErrorFor("code");
    }
}
