<?php

namespace App\Listeners;

use App\Events\TweetRetweeted;
use App\Notifications\NewTweetRetweeted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNewTweetRetweetedNotification
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
     * @param  \App\Events\TweetRetweeted  $event
     * @return void
     */
    public function handle(TweetRetweeted $event)
    {
        $event->tweetRetweeted->user->notify( new NewTweetRetweeted($event->tweetRetweeted, $event->retweetOwner) );
    }
}
