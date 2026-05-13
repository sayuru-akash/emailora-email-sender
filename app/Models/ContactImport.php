<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ContactImport extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'mapping' => 'array',
        'preview_rows' => 'array',
        'assigned_list_ids' => 'array',
        'assigned_tag_ids' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (ContactImport $import) => $import->uuid ??= (string) Str::uuid());
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    public function getProgressPercentAttribute(): float
    {
        return $this->total_rows > 0 ? round(($this->processed_rows / $this->total_rows) * 100, 1) : 0.0;
    }
}
