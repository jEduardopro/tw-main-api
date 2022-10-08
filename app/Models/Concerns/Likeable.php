<?php

namespace App\Models\Concerns;

use App\Events\ModelLiked;
use App\Events\ModelUnliked;
use App\Models\Like;
use Illuminate\Support\Str;

trait Likeable
{

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function like()
    {
        $likeSender = request()->user();
        $this->likes()->firstOrCreate([
            "user_id" => $likeSender->id
        ]);

        ModelLiked::dispatch($this, $likeSender);
    }

    public function unlike()
    {
        $likeSender = request()->user();
        $this->likes()->where([
            "user_id" => $likeSender->id
        ])->delete();

        ModelUnliked::dispatch($this);
    }

    public function isLiked()
    {
        if (!request()->user()) {
            return false;
        }
        return $this->likes()->where('user_id', request()->user()->id)->exists();
    }

    public function eventChannelName(): string
    {
        return Str::of(class_basename($this))->lower()->plural() . ".{$this->uuid}.likes";
    }

}
