<?php

namespace App\Events;

use App\Models\Tweet;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeletedTweetReply implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Tweet $tweetReplying;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($tweetReplying)
    {
        $this->dontBroadcastToCurrentUser();

        $this->tweetReplying = $tweetReplying;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel("tweets.{$this->tweetReplying->uuid}.replies");
    }
}
