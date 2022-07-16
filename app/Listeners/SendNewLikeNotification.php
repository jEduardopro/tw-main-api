<?php

namespace App\Listeners;

use App\Events\ModelLiked;
use App\Notifications\NewLike;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNewLikeNotification
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
     * @param  \App\Events\ModelLiked  $event
     * @return void
     */
    public function handle(ModelLiked $event)
    {
        $event->model->user->notify(new NewLike($event->model, $event->likeSender));
    }
}
