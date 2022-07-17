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


class NewRepliedTweet extends Notification
{
    use Queueable;

    public Tweet $replyTweet;
    public User $userReplying;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($replyTweet, $userReplying)
    {
        $this->replyTweet = $replyTweet->load(["user.profileImage"]);
        $this->userReplying = $userReplying;
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
            "reply_tweet" => TweetResource::make($this->replyTweet)->resolve(),
            "user_replying" => ProfileResource::make($this->userReplying)->resolve(),
        ];
    }

    public function toDatabase($notifiable)
    {
        return array_merge([
            "reply_tweet_uuid" => $this->replyTweet->uuid,
        ], $this->toArray($notifiable));
    }

    public function toBroadcast($notifiable)
    {
        return (new BroadcastMessage($this->toArray($notifiable)))->onQueue("replies");
    }

    /**
     * Get the type of the notification being broadcast.
     *
     * @return string
     */
    public function broadcastType()
    {
        return 'reply.added';
    }

    public function saveNotification($notifiable): CustomDatabaseNotification
    {
        return CustomDatabaseNotification::create([
            "id" => Str::uuid()->toString(),
            "type" => self::class,
            "notifiable_type" => get_class($notifiable),
            "notifiable_id" => $notifiable->id,
            "senderable_type" => get_class($this->replyTweet->user),
            "senderable_id" => $this->replyTweet->user->id,
            "data" => $this->toDatabase($notifiable)
        ]);
    }
}
