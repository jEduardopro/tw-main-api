<?php

namespace Tests\Feature\App\Http\Controllers\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailActivation;
use App\Notifications\VerifyPhoneActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class VerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_verify_their_email()
    {
        $user = User::factory()->unverified()->withoutPassword()->create($this->userValidData(['phone' => null]));
        $code = Str::upper(Str::random(8));
        $userVerificationData = ['user_id' => $user->id, 'code' => $code];
        DB::table('user_verifications')->insert($userVerificationData);

        $this->assertFalse($user->hasVerifiedEmail());

        $response = $this->postJson('api/auth/verification/verify', ['code' => $code]);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $response->assertSuccessful()
            ->assertJsonStructure([
                'message',
                'verified_at'
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $user->email,
            'email_verified_at' => $response->json('verified_at'),
            'is_activated' => false
        ]);

        $this->assertDatabaseMissing('user_verifications', $userVerificationData);
    }

    /** @test */
    public function a_user_can_verify_their_phone()
    {
        $user = User::factory()->unverified()
            ->withoutPassword()
            ->withPhoneValidated()
            ->create(['email' => null]);

        $code = Str::upper(Str::random(8));
        $userVerificationData = ['user_id' => $user->id, 'code' => $code];
        DB::table('user_verifications')->insert($userVerificationData);

        $this->assertFalse($user->hasVerifiedPhone());

        $response = $this->postJson('api/auth/verification/verify', ['code' => $code]);

        $this->assertTrue($user->fresh()->hasVerifiedPhone());

        $response->assertSuccessful()
            ->assertJsonStructure([
                'message',
                'verified_at'
            ]);

        $this->assertDatabaseHas('users', [
            'country_code' => $user->country_code,
            'phone' => $user->phone,
            'is_activated' => false
        ]);

        $this->assertDatabaseMissing('user_verifications', $userVerificationData);
    }

    /** @test */
    public function the_verify_process_to_email_or_phone_must_be_fail_if_does_not_exist_a_user_verification_code()
    {
        $code = Str::upper(Str::random(8));

        $response = $this->postJson('api/auth/verification/verify', ['code' => $code])
                ->assertStatus(400);

        $this->assertEquals("The code you entered is incorrect", $response->json("message"));
    }

    /** @test */
    public function the_verify_process_to_email_or_phone_must_be_fail_if_there_is_no_registered_user()
    {
        $user = User::factory()->unverified()
            ->withoutPassword()
            ->withPhoneValidated()
            ->create(['email' => null]);

        $code = Str::upper(Str::random(8));
        $userVerificationData = ['user_id' => $user->id, 'code' => $code];
        DB::table('user_verifications')->insert($userVerificationData);

        $user->delete();
        $response = $this->postJson('api/auth/verification/verify', ['code' => $code]);

        $response->assertStatus(400);
        $this->assertEquals("The account does not exist", $response->json("message"));
    }

    /** @test */
    public function a_user_can_resend_the_verification_code_by_email()
    {
        Notification::fake();

        $user = User::factory()->unverified()->withoutPassword()->create($this->userValidData(['phone' => null]));
        $code = Str::upper(Str::random(8));
        $userVerificationData = ['user_id' => $user->id, 'code' => $code];
        DB::table('user_verifications')->insert($userVerificationData);

        $this->assertFalse($user->hasVerifiedEmail());

        $payload = [
            "email" => $user->email,
            "description" => User::SIGN_UP_DESC_EMAIL
        ];
        $response = $this->postJson('api/auth/verification/resend', $payload);
        $response->assertSuccessful()
                ->assertJsonStructure([
                    "message"
                ]);

        $this->assertEquals("The code was sent successfully", $response->json('message'));

        $this->assertDatabaseMissing("user_verifications", $userVerificationData);

        Notification::assertSentTo($user, VerifyEmailActivation::class);
    }

    /** @test */
    public function a_user_can_resend_the_verification_code_by_sms()
    {
        Notification::fake();

        $user = User::factory()->unverified()->withoutPassword()
            ->withPhoneValidated()
            ->create(['email' => null]);

        $code = Str::upper(Str::random(8));
        $userVerificationData = ['user_id' => $user->id, 'code' => $code];
        DB::table('user_verifications')->insert($userVerificationData);

        $this->assertFalse($user->hasVerifiedPhone());

        $payload = [
            "phone" => $user->phone,
            "description" => User::SIGN_UP_DESC_PHONE
        ];
        $response = $this->postJson('api/auth/verification/resend', $payload);
        $response->assertSuccessful()
                ->assertJsonStructure([
                    "message"
                ]);
        $this->assertEquals("The code was sent successfully", $response->json('message'));

        $this->assertDatabaseMissing("user_verifications", $userVerificationData);

        Notification::assertSentTo($user, VerifyPhoneActivation::class);
    }

    /** @test */
    public function a_user_account_verified_cannot_verify_again()
    {
        $user = User::factory()->create();

        $code = Str::upper(Str::random(8));
        $userVerificationData = ['user_id' => $user->id, 'code' => $code];
        DB::table('user_verifications')->insert($userVerificationData);
        $response = $this->postJson('api/auth/verification/verify', ['code' => $code]);

        $response->assertStatus(403);

        $this->assertEquals("The user account is already verified", $response->json('message'));

        $this->assertDatabaseMissing("user_verifications", $userVerificationData);
    }

    /** @test */
    public function the_verification_code_must_be_valid()
    {
        $user = User::factory()->unverified()->withoutPassword()->create($this->userValidData(['email' => null]));
        $code = Str::upper(Str::random(8));
        $userVerificationData = ['user_id' => $user->id, 'code' => $code, 'created_at' => now()->subMinutes(15)];
        DB::table('user_verifications')->insert($userVerificationData);

        $this->assertDatabaseHas('user_verifications', $userVerificationData);

        $response = $this->postJson('api/auth/verification/verify', ['code' => $code]);
        $response->assertStatus(400);

        $this->assertEquals("The code you entered is expired", $response->json("message"));
    }

    /** @test */
    public function the_verification_code_is_required()
    {
        $this->postJson('api/auth/verification/verify', ['code' => null])
            ->assertJsonValidationErrorFor('code')
            ->assertJsonValidationErrors([
                'code' => ['The code field is required.']
            ]);
    }


    /** @test */
    public function the_verification_code_must_no_have_more_than_8_characters()
    {
        $this->postJson('api/auth/verification/verify', ['code' => '123456789'])
            ->assertJsonValidationErrorFor('code');
    }

    /** @test */
    public function the_verification_code_should_not_be_sent_if_there_is_no_user()
    {
        $payload = [
            "email" => "no_email@invalid.com",
            "description" => User::SIGN_UP_DESC_EMAIL
        ];
        $this->postJson('api/auth/verification/resend', $payload)
            ->assertStatus(417)
            ->assertExactJson([
                "message" => "The code was not sent, the information is invalid"
            ]);
    }

    /** @test */
    public function the_email_must_be_required_when_we_resend_verification_code_is_by_mail()
    {
        $payload = [
            "description" => User::SIGN_UP_DESC_EMAIL
        ];
        $this->postJson('api/auth/verification/resend', $payload)
            ->assertJsonValidationErrorFor('email');
    }

    /** @test */
    public function the_email_must_be_a_valid_email_address()
    {
        $payload = [
            "email" => "invalidemail.com",
            "description" => User::SIGN_UP_DESC_EMAIL
        ];
        $this->postJson('api/auth/verification/resend', $payload)
            ->assertJsonValidationErrorFor('email');
    }

    /** @test */
    public function the_phone_must_be_required_when_we_resend_verification_code_is_by_sms()
    {
        $payload = [
            "description" => User::SIGN_UP_DESC_PHONE
        ];
        $this->postJson('api/auth/verification/resend', $payload)
            ->assertJsonValidationErrorFor('phone');
    }

    /** @test */
    public function the_phone_must_be_a_phone_number_valid()
    {
        $payload = [
            "phone" => "00-invalid-phone",
            "description" => User::SIGN_UP_DESC_PHONE
        ];
        $this->postJson('api/auth/verification/resend', $payload)
            ->assertJsonValidationErrorFor('phone');
    }

    /** @test */
    public function the_description_is_required()
    {
        $payload = [];
        $this->postJson('api/auth/verification/resend', $payload)
            ->assertJsonValidationErrorFor('description');
    }

    /** @test */
    public function the_description_must_be_string()
    {
        $payload = [
            "description" => 154545677
        ];
        $this->postJson('api/auth/verification/resend', $payload)
            ->assertJsonValidationErrorFor('description');
    }

    /** @test */
    public function the_description_must_be_a_valid_sign_up_description()
    {
        $payload = [
            "description" => "sign_up_invalid"
        ];
        $this->postJson('api/auth/verification/resend', $payload)
            ->assertJsonValidationErrorFor('description');
    }


}
