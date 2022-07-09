<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reply extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the tweet that owns the Reply
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tweet(): BelongsTo
    {
        return $this->belongsTo(Tweet::class);
    }


    /**
     * Get the tweetReply associated with the Reply
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function tweetReply(): HasOne
    {
        return $this->hasOne(Tweet::class);
    }
}
