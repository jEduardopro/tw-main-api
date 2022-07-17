<?php

namespace App\Events;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RepliedTweet implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Tweet $replyTweet;
    public User $userReplying;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($replyTweet, $userReplying)
    {
        $this->dontBroadcastToCurrentUser();

        $this->replyTweet = $replyTweet;
        $this->userReplying = $userReplying;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $tweetReplying = $this->replyTweet->reply->tweet;
        return new Channel("tweets.{$tweetReplying->uuid}.replies");
    }
}
