<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flow extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        "name",
        "token",
        "payload"
    ];

    protected $casts = [
        "payload" => "array"
    ];

}
