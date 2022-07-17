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


class NewTweetRetweeted extends Notification
{
    use Queueable;

    public Tweet $tweetRetweeted;
    public User $retweetOwner;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($tweetRetweeted, $retweetOwner)
    {
        $this->tweetRetweeted = $tweetRetweeted;
        $this->retweetOwner = $retweetOwner;
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
            "tweet_retweeted" => TweetResource::make($this->tweetRetweeted)->resolve(),
            "retweet_owner" => ProfileResource::make($this->retweetOwner)->resolve(),
        ];
    }

    public function toDatabase($notifiable)
    {
        return [
            "tweet_retweeted_uuid" => $this->tweetRetweeted->uuid,
            "tweet_retweeted" => TweetResource::make($this->tweetRetweeted)->resolve(),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return (new BroadcastMessage($this->toArray($notifiable)))->onQueue("retweets");
    }

    /**
     * Get the type of the notification being broadcast.
     *
     * @return string
     */
    public function broadcastType()
    {
        return 'retweet.added';
    }

    public function saveNotification($notifiable): CustomDatabaseNotification
    {
        return CustomDatabaseNotification::create([
            "id" => Str::uuid()->toString(),
            "type" => self::class,
            "notifiable_type" => get_class($notifiable),
            "notifiable_id" => $notifiable->id,
            "senderable_type" => get_class($this->retweetOwner),
            "senderable_id" => $this->retweetOwner->id,
            "data" => $this->toDatabase($notifiable)
        ]);
    }
}
