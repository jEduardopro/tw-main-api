<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Notifications\VerifyEmailActivation;
use App\Notifications\VerifyPhoneActivation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendVerificationCodeNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UserRegistered  $event
     * @return void
     */
    public function handle(UserRegistered $event)
    {
        if (! $event->user->hasVerifiedEmail() && $event->user->email) {
            $event->user->notify(new VerifyEmailActivation($event->user->code));
        }

        if (! $event->user->hasVerifiedPhone() && $event->user->phone_validated) {
            $event->user->notify(new VerifyPhoneActivation($event->user->phone_validated, $event->user->code));
        }
    }
}
