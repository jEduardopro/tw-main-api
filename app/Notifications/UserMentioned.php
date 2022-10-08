<?php

namespace App\Notifications;

use App\Http\Resources\TweetResource;
use App\Models\CustomDatabaseNotification;
use App\Models\Tweet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;


class UserMentioned extends Notification
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
        $this->saveNotification($notifiable);
        return ['broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
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
        ];
    }

    public function toDatabase($notifiable)
    {
        return array_merge([
            "tweet_uuid" => $this->tweet->uuid,
        ], $this->toArray($notifiable));
    }

    public function toBroadcast($notifiable)
    {
        return (new BroadcastMessage($this->toArray($notifiable)))->onQueue("mentions");
    }

    /**
     * Get the type of the notification being broadcast.
     *
     * @return string
     */
    public function broadcastType()
    {
        return 'user.mentioned';
    }

    public function saveNotification($notifiable): CustomDatabaseNotification
    {
        return CustomDatabaseNotification::create([
            "id" => Str::uuid()->toString(),
            "type" => self::class,
            "notifiable_type" => get_class($notifiable),
            "notifiable_id" => $notifiable->id,
            "senderable_type" => get_class($this->tweet->user),
            "senderable_id" => $this->tweet->user->id,
            "data" => $this->toDatabase($notifiable)
        ]);
    }
}
