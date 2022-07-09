<?php

namespace App\Notifications;

use App\Http\Resources\TweetResource;
use App\Models\Tweet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TweetCreated extends Notification
{
    use Queueable;

    public Tweet $tweet;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Tweet $tweet)
    {
        $this->tweet = $tweet;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return TweetResource::make($this->tweet)->resolve();
    }

    public function toBroadcast($notifiable)
    {
        return (new BroadcastMessage($this->toArray($notifiable)))->onQueue('tweets');
    }

    /**
     * Get the type of the notification being broadcast.
     *
     * @return string
     */
    public function broadcastType()
    {
        return 'tweet.added';
    }
}
