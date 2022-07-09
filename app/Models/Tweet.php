<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\Likeable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Str;

class Tweet extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, HasUuid, Likeable;

    protected $fillable = ["body"];

    /** Relationships */

    /**
     * Get the user that owns the Tweet
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all of the retweets for the Tweet
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function retweets(): HasMany
    {
        return $this->hasMany(Retweet::class);
    }


    /**
     * Get the reply that owns the Tweet
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reply(): BelongsTo
    {
        return $this->belongsTo(Reply::class);
    }

    /**
     * Get all of the replies for the Tweet
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }

    /**
     * Get all of the tweetReplies for the Tweet
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function tweetReplies(): HasManyThrough
    {
        return $this->hasManyThrough(Tweet::class, Reply::class);
    }

    /** Scopes */
    public function scopeSearchByImageTerm($query, string $q)
    {
        $tweetIds = Media::select('model_id')->where('name', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%". Str::slug($q) . "%")
                        ->orWhere('name', 'like', "%". Str::slug($q, "_") . "%")
                        ->where('model_type', Tweet::class);

        $query->whereIn('id', $tweetIds);
    }

    /** Public Methods */

    /**
     * Move media files uploeded by user to tweet model
     */
    public function attachMediaFiles(): void
    {
        $user = request()->user();

        if (!request()->filled('media')) {
            return;
        }

        $media = $user->media()->whereIn('uuid', request()->media)->get();

        $media->each(function (Media $mediaItem) {
            $mediaItem->move($this, 'images', 'media');
        });
    }

    /**
     * Delete media files of a tweet
     */
    public function detachMediaFiles(): void
    {
        $this->media()->get()->each(function($media) {
            $media->delete();
        });
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('small')
            ->width(150)
            ->height(150);

        $this->addMediaConversion('thumb')
            ->width(360)
            ->height(360);


        $this->addMediaConversion('medium')
            ->width(680)
            ->height(380)
            ->nonQueued();

        $this->addMediaConversion('large')
            ->width(1200)
            ->height(675);
    }

    /**
     * Returns date of post of a tweet readable for humans
     */
    public function getReadableCreationDate(): string
    {
        $date = $this->created_at;
        return $date->format('H:i a') . " · " . $date->format('M j') . "," . $date->format("Y");
    }

}
