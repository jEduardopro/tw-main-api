<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\InteractsWithMedia;

class Tweet extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = ["body"];

    public function attachMediaFiles()
    {
        $user = request()->user();

        if (!request()->filled('media')) {
            return;
        }

        $media = $user->media()->whereIn('id', request()->media)->get();

        $media->each(function (Media $mediaItem) {
            $mediaItem->move($this, 'images', 'media');
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
            ->height(380);

        $this->addMediaConversion('large')
            ->width(1200)
            ->height(675);
    }
}
