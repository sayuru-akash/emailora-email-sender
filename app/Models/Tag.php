<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (Tag $tag): void {
            $tag->slug = $tag->slug ?: Str::slug($tag->name);
        });
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)->withTimestamps();
    }

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"));
    }
}
