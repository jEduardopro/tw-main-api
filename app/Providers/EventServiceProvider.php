<?php

namespace App\Providers;

use App\Events\ModelLiked;
use App\Events\UserRegistered;
use App\Listeners\SendNewLikeNotification;
use App\Listeners\SendVerificationTokenNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Registered::class => [
        //     SendEmailVerificationNotification::class,
        // ],
        UserRegistered::class => [
            SendVerificationTokenNotification::class
        ],
        ModelLiked::class => [
            SendNewLikeNotification::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
