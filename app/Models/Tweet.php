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
}
