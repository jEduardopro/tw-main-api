<?php

namespace Tests\Unit\App\Listeners;

use App\Events\UserRegistered;
use App\Models\User;
use App\Notifications\VerifyEmailActivation;
use App\Notifications\VerifyPhoneActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class SendActivationNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_notification_is_sent_when_a_user_is_registered_user_by_email()
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'name' => 'name test',
            'email' => 'example_test@example.com'
        ]);

        // set token code on the fly
        $user['token'] = Str::upper( Str::random(6) );

        UserRegistered::dispatch($user);

        Notification::assertSentTo($user, VerifyEmailActivation::class);
    }

    /** @test */
    public function a_notification_is_sent_when_a_user_is_registered_user_by_phone()
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'name' => 'name test',
            'email' => null,
            'phone' => '+528421133471'
        ]);

        // set token code on the fly
        $user['token'] = Str::upper( Str::random(6) );

        UserRegistered::dispatch($user);

        Notification::assertSentTo($user, VerifyPhoneActivation::class);
    }
}
