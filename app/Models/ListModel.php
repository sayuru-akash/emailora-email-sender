<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ListModel extends Model
{
    use HasFactory;

    protected $table = 'lists';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (ListModel $list): void {
            $list->slug = $list->slug ?: Str::slug($list->name);
        });
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_list', 'list_id', 'contact_id')->withTimestamps();
    }

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"));
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
