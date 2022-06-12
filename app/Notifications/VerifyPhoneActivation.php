<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Twilio\Rest\Client;

class VerifyPhoneActivation extends Notification
{
    use Queueable;


    protected $twilioClient;
    protected $twilioPhoneNumber;
    protected $phoneNumber;
    protected $code;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($phoneNumber, $code)
    {
        $account_sid = env('TWILIO_SID');
        $auth_token = env('TWILIO_AUTH_TOKEN');
        $this->twilioPhoneNumber = "+17372043373";
        $this->phoneNumber = $phoneNumber;
        $this->code = $code;

        $this->twilioClient = new Client($account_sid, $auth_token);
        if (config('app.env') == 'testing') {
            info('test sms notification sent');
            return;
        }
        $this->sendSms();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [];
    }

    private function sendSms()
    {
        $this->twilioClient->messages->create(
            // Where to send a text message (your cell phone?)
            $this->phoneNumber,
            array(
                'from' => $this->twilioPhoneNumber,
                'body' => "This is the security verification code for TwitterClone: {$this->code}"
            )
        );
    }
}
