<?php

namespace App\Notifications;

use App\Http\Resources\ProfileResource;
use App\Http\Resources\TweetResource;
use App\Models\Tweet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLike extends Notification
{
    use Queueable;

    public Tweet $tweet;
    public $likeSender;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($tweet, $likeSender)
    {
        $this->tweet = $tweet;
        $this->likeSender = $likeSender->load('profileImage');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['broadcast', 'database'];
    }


    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            "tweet" => TweetResource::make($this->tweet)->resolve(),
            "like_sender" => ProfileResource::make($this->likeSender)->resolve(),
        ];
    }

    public function toDatabase($notifiable)
    {
        return array_merge([
            "tweet_uuid" => $this->tweet->uuid,
            "like_sender_uuid" => $this->likeSender->uuid
        ], $this->toArray($notifiable));
    }

    public function toBroadcast($notifiable)
    {
        return (new BroadcastMessage($this->toArray($notifiable)))->onQueue('likes');
    }

    /**
     * Get the type of the notification being broadcast.
     *
     * @return string
     */
    public function broadcastType()
    {
        return 'like.added';
    }
}
