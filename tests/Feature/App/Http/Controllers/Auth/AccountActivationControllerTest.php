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

class AccountActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_activate_their_account_by_verifying_their_email()
    {
        $user = User::factory()->unverified()->withoutPassword()->create($this->userValidData(['phone' => null]));
        $token = Str::upper(Str::random(6));
        $userActivationData = ['user_id' => $user->id, 'token' => $token];
        DB::table('user_activations')->insert($userActivationData);

        $this->assertFalse($user->hasVerifiedEmail());

        $response = $this->postJson('api/account/activation', ['token' => $token]);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $response->assertSuccessful()
            ->assertJsonStructure([
                'message',
                'token',
                'user'
            ]);

        $this->assertDatabaseMissing('user_activations', $userActivationData);
    }
    /** @test */
    public function a_user_can_activate_their_account_by_verifying_their_phone()
    {
        $user = User::factory()->unverified()
            ->withoutPassword()
            ->withPhoneValidated()
            ->create(['email' => null]);

        $token = Str::upper(Str::random(6));
        $userActivationData = ['user_id' => $user->id, 'token' => $token];
        DB::table('user_activations')->insert($userActivationData);

        $this->assertFalse($user->hasVerifiedPhone());

        $response = $this->postJson('api/account/activation', ['token' => $token]);

        $this->assertTrue($user->fresh()->hasVerifiedPhone());

        $response->assertSuccessful()
            ->assertJsonStructure([
                'message',
                'token',
                'user'
            ]);

        $this->assertEquals($response->json('user.phone'), env('PHONE_NUMBER_VALIDATED_TEST'));
        $this->assertDatabaseHas('users', [
            'country_code' => env('COUNTRY_CODE_TEST'),
            'phone' => env('PHONE_NUMBER_VALIDATED_TEST')
        ]);

        $this->assertDatabaseMissing('user_activations', $userActivationData);
    }

    /** @test */
    public function a_user_can_resend_the_verification_token_by_email()
    {
        Notification::fake();

        $user = User::factory()->unverified()->withoutPassword()->create($this->userValidData(['phone' => null]));
        $token = Str::upper(Str::random(6));
        $userActivationData = ['user_id' => $user->id, 'token' => $token];
        DB::table('user_activations')->insert($userActivationData);

        $this->assertFalse($user->hasVerifiedEmail());

        $payload = [
            "email" => $user->email,
            "description" => User::SIGN_UP_DESC_EMAIL
        ];
        $response = $this->postJson('api/account/activation/resend', $payload);
        $response->assertSuccessful()
                ->assertJsonStructure([
                    "message"
                ]);
        $this->assertEquals("The code was sent successfully", $response->json('message'));

        $this->assertDatabaseMissing("user_activations", $userActivationData);

        Notification::assertSentTo($user, VerifyEmailActivation::class);
    }

    /** @test */
    public function a_user_can_resend_the_verification_token_by_sms()
    {
        Notification::fake();

        $user = User::factory()->unverified()->withoutPassword()
            ->withPhoneValidated()
            ->create(['email' => null]);

        $token = Str::upper(Str::random(6));
        $userActivationData = ['user_id' => $user->id, 'token' => $token];
        DB::table('user_activations')->insert($userActivationData);

        $this->assertFalse($user->hasVerifiedPhone());

        $payload = [
            "phone" => $user->phone,
            "description" => User::SIGN_UP_DESC_PHONE
        ];
        $response = $this->postJson('api/account/activation/resend', $payload);
        $response->assertSuccessful()
                ->assertJsonStructure([
                    "message"
                ]);
        $this->assertEquals("The code was sent successfully", $response->json('message'));

        $this->assertDatabaseMissing("user_activations", $userActivationData);

        Notification::assertSentTo($user, VerifyPhoneActivation::class);
    }

    /** @test */
    public function the_verification_code_must_be_valid()
    {
        $user = User::factory()->unverified()->withoutPassword()->create($this->userValidData(['email' => null]));
        $token = Str::upper(Str::random(6));
        $userActivationData = ['user_id' => $user->id, 'token' => $token, 'created_at' => now()->subMinutes(5)];
        DB::table('user_activations')->insert($userActivationData);

        $this->assertDatabaseHas('user_activations', $userActivationData);

        $response = $this->postJson('api/account/activation', ['token' => $token]);
        $response->assertJsonValidationErrorFor('token');
    }

    /** @test */
    public function the_verification_code_is_required()
    {
        $this->postJson('api/account/activation', ['token' => null])
            ->assertJsonValidationErrorFor('token')
            ->assertJsonValidationErrors([
                'token' => ['The token field is required.']
            ]);
    }

    /** @test */
    public function the_verification_code_should_not_be_sent_if_there_is_no_user()
    {
        $payload = [
            "email" => "no_email@invalid.com",
            "description" => User::SIGN_UP_DESC_EMAIL
        ];
        $this->postJson('api/account/activation/resend', $payload)
            ->assertStatus(417)
            ->assertExactJson([
                "message" => "The code was not sent, the information is invalid"
            ]);
    }

    /** @test */
    public function the_email_must_be_required_when_we_resend_activation_code_is_by_mail()
    {
        $payload = [
            "description" => User::SIGN_UP_DESC_EMAIL
        ];
        $this->postJson('api/account/activation/resend', $payload)
            ->assertJsonValidationErrorFor('email');
    }

    /** @test */
    public function the_email_must_be_a_valid_email_address()
    {
        $payload = [
            "email" => "invalidemail.com",
            "description" => User::SIGN_UP_DESC_EMAIL
        ];
        $this->postJson('api/account/activation/resend', $payload)
            ->assertJsonValidationErrorFor('email');
    }

    /** @test */
    public function the_phone_must_be_required_when_we_resend_activation_code_is_by_sms()
    {
        $payload = [
            "description" => User::SIGN_UP_DESC_PHONE
        ];
        $this->postJson('api/account/activation/resend', $payload)
            ->assertJsonValidationErrorFor('phone');
    }

    /** @test */
    public function the_phone_must_be_a_phone_number_valid()
    {
        $payload = [
            "phone" => "00-invalid-phone",
            "description" => User::SIGN_UP_DESC_PHONE
        ];
        $this->postJson('api/account/activation/resend', $payload)
            ->assertJsonValidationErrorFor('phone');
    }

    /** @test */
    public function the_description_is_required()
    {
        $payload = [];
        $this->postJson('api/account/activation/resend', $payload)
            ->assertJsonValidationErrorFor('description');
    }

    /** @test */
    public function the_description_must_be_string()
    {
        $payload = [
            "description" => 154545677
        ];
        $this->postJson('api/account/activation/resend', $payload)
            ->assertJsonValidationErrorFor('description');
    }

    /** @test */
    public function the_description_must_be_a_valid_sign_up_description()
    {
        $payload = [
            "description" => "sign_up_invalid"
        ];
        $this->postJson('api/account/activation/resend', $payload)
            ->assertJsonValidationErrorFor('description');
    }


}
