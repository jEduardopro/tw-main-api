<?php

namespace Tests\Feature\App\Http\Controllers\Auth;

use App\Events\UserRegistered;
use App\Models\User;
use App\Events\VerifyEmailActivation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Illuminate\Support\Str;
use Tests\TestCase;

class SignUpTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_register_with_email()
    {
        Event::fake();

        $response = $this->postJson('api/auth/register', $this->userValidData(['phone' => null]));

        $response->assertSuccessful();

        $response->assertJsonStructure([
            'user' => ['name', 'email']
        ]);

        Event::assertDispatched(UserRegistered::class);

        $this->assertDatabaseHas('users', $this->userValidData(['phone' => null]));
        $this->assertDatabaseHas('user_activations', [
            'user_id' => $response->json('user.id')
        ]);
    }

    /** @test */
    public function a_user_can_activate_their_account_by_verifying_their_email()
    {
        $user = User::factory()->unverified()->withoutPassword()->create($this->userValidData(['phone' => null]));
        $token = Str::random(10);
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
    public function a_user_can_register_with_phone()
    {
        Event::fake();

        $response = $this->postJson('api/auth/register', $this->userValidData(['email' => null]));

        $response->assertSuccessful();

        $response->assertJsonStructure([
            'user' => ['name', 'phone']
        ]);

        Event::assertDispatched(UserRegistered::class);

        $this->assertDatabaseHas('users', $this->userValidData(['email' => null]));
        $this->assertDatabaseHas('user_activations', [
            'user_id' => $response->json('user.id')
        ]);
    }

    /** @test */
    public function a_user_can_activate_their_account_by_verifying_their_phone()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->unverified()
            ->withoutPassword()
            ->create($this->userValidData(['email' => null]));

        $token = Str::random(10);
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
    public function the_verification_code_must_be_valid()
    {
        $user = User::factory()->unverified()->withoutPassword()->create($this->userValidData(['email' => null]));
        $token = Str::random(10);
        $userActivationData = ['user_id' => $user->id, 'token' => $token, 'created_at' => now()->subMinutes(5)];
        DB::table('user_activations')->insert($userActivationData);

        $this->assertDatabaseHas('user_activations', $userActivationData);

        $response = $this->postJson('api/account/activation', ['token' => $token]);
        $response->assertJsonValidationErrorFor('token');
    }


    /** @test */
    public function the_name_is_required()
    {
        $this->postJson('api/auth/register', $this->userValidData(["name" => null]))
            ->assertJsonValidationErrorFor('name');
    }

    /** @test */
    public function the_email_must_be_a_valid_email_address()
    {
        $this->postJson('api/auth/register', $this->userValidData(["email" => "invalid_email_address"]))
            ->assertJsonValidationErrorFor('email');
    }

    /** @test */
    public function the_email_must_be_unique()
    {
        User::factory()->create(["email" => "test_email@gmail.com"]);
        $this->postJson('api/auth/register', $this->userValidData(["email" => "test_email@gmail.com"]))
            ->assertJsonValidationErrorFor('email');
    }

    /** @test */
    public function the_phone_must_be_phone_number_valid()
    {
        $this->postJson('api/auth/register', $this->userValidData(["phone" => '863863']))
            ->assertJsonValidationErrorFor('phone');
    }

    /** @test */
    public function the_request_must_have_at_least_an_email_or_a_phone()
    {
        $this->postJson('api/auth/register', $this->userValidData(["phone" => null, "email" => null]))
            ->assertStatus(422);
    }

    private function userValidData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'name test',
            'email' => 'example_test@example.com',
            'phone' => env("PHONE_NUMBER_TEST")
        ], $overrides);
    }
}
