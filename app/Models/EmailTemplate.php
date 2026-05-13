<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'variables' => 'array',
    ];

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('subject', 'like', "%{$search}%"));
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
