<?php

namespace App\Notifications;

use App\Http\Resources\ProfileResource;
use App\Http\Resources\TweetResource;
use App\Models\CustomDatabaseNotification;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;


class NewLike extends Notification
{
    use Queueable;

    public Tweet $tweet;
    public User $likeSender;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($tweet, $likeSender)
    {
        $this->tweet = $tweet->load(["user.profileImage", "media", "mentions.profileImage"])->loadCount(["retweets", "replies", "likes"]);
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
        $this->saveNotification($notifiable);
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
        return [
            "tweet" => TweetResource::make($this->tweet)->resolve(),
            "like_sender" => ProfileResource::make($this->likeSender)->resolve(),
        ];
    }

    public function toDatabase($notifiable)
    {
        return ["tweet_uuid" => $this->tweet->uuid, "tweet" => TweetResource::make($this->tweet)];
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

    public function saveNotification($notifiable): CustomDatabaseNotification
    {
        return CustomDatabaseNotification::create([
            "id" => Str::uuid()->toString(),
            "type" => self::class,
            "notifiable_type" => get_class($notifiable),
            "notifiable_id" => $notifiable->id,
            "senderable_type" => get_class($this->likeSender),
            "senderable_id" => $this->likeSender->id,
            "data" => $this->toDatabase($notifiable)
        ]);
    }
}
