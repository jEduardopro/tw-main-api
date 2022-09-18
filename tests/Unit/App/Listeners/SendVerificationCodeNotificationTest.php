<?php

namespace Tests\Unit\App\Listeners;

use App\Events\UserRegistered;
use App\Models\User;
use App\Notifications\VerifyEmailActivation;
use App\Notifications\VerifyPhoneActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class SendVerificationCodeNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_notification_is_sent_when_a_user_is_registered_user_by_email()
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'example_test@example.com'
        ]);

        // set code on the fly
        $user['code'] = Str::upper( Str::random(8) );

        UserRegistered::dispatch($user);

        Notification::assertSentTo($user, VerifyEmailActivation::class, function($notification, $channels) use ($user){
            $mail = $notification->toMail($user);

            $this->assertInstanceOf(MailMessage::class, $mail);
            $this->assertContains("mail", $channels);
            $this->assertTrue(!is_null($notification->code));
            $this->assertEquals($notification->code, $user["code"]);
            $this->assertArrayHasKey("code", $mail->viewData);
            return true;
        });
    }

    /** @test */
    public function a_notification_is_sent_when_a_user_is_registered_user_by_phone()
    {
        Notification::fake();

        $user = User::factory()->unverified()
            ->withPhoneValidated()
            ->create([
                'email' => null,
            ]);

        // set code on the fly
        $user['code'] = Str::upper( Str::random(8) );

        UserRegistered::dispatch($user);

        Notification::assertSentTo($user, VerifyPhoneActivation::class, function($notification) use ($user){
            $this->assertTrue(!is_null($notification->phoneNumber));
            $this->assertEquals($notification->phoneNumber, $user->phone_validated);
            $this->assertTrue(!is_null($notification->code));
            $this->assertEquals($notification->code, $user["code"]);
            return true;
        });
    }
}
