<?php

namespace App\Models\Concerns;

use App\Models\Like;

trait Likeable
{

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function like()
    {
        $this->likes()->firstOrCreate([
            "user_id" => request()->user()->id
        ]);
    }

    public function unlike()
    {
        $this->likes()->where([
            "user_id" => request()->user()->id
        ])->delete();
    }

}
