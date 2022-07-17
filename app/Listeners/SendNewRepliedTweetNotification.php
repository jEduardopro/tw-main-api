<?php

namespace App\Listeners;

use App\Events\RepliedTweet;
use App\Notifications\NewRepliedTweet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNewRepliedTweetNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\RepliedTweet  $event
     * @return void
     */
    public function handle(RepliedTweet $event)
    {
        $event->userReplying->notify(new NewRepliedTweet($event->replyTweet, $event->userReplying));
    }
}
