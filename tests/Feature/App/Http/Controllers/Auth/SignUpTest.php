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

        $response = $this->postJson('api/account/activation/email', ['token' => $token]);

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

    // /** @test */
    // public function name_is_required()
    // {
    //     $response = $this->postJson()
    // }

    private function userValidData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'name test',
            'email' => 'example_test@example.com',
            'phone' => '1234567890'
        ], $overrides);
    }
}
