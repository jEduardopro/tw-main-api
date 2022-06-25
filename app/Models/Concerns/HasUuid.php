<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasUuid
{
    protected static function booted(): void
    {
        static::creating(function($model) {
            $model->uuid = Str::uuid()->toString();
        });
    }
}
