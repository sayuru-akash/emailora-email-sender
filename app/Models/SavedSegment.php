<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SavedSegment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'filters' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(fn (SavedSegment $segment) => $segment->slug = $segment->slug ?: Str::slug($segment->name));
    }
}
