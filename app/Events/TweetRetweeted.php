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

class TweetRetweeted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Tweet $tweetRetweeted;
    public User $retweetOwner;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($tweetRetweeted, $retweetOwner)
    {
        $this->dontBroadcastToCurrentUser();

        $this->tweetRetweeted = $tweetRetweeted;
        $this->retweetOwner = $retweetOwner;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel("tweets.{$this->tweetRetweeted->uuid}.retweets");
    }
}
