<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

class CustomDatabaseNotification extends DatabaseNotification
{
    use HasFactory;

    /**
     * Get the senderable entity that the notification belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function senderable()
    {
        return $this->morphTo();
    }
}
