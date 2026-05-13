<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EmailCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'target_filters' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (EmailCampaign $campaign) => $campaign->uuid ??= (string) Str::uuid());
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('subject', 'like', "%{$search}%"));
    }

    public function scopeByStatus($query, ?string $status)
    {
        return $query->when($status, fn ($query) => $query->where('status', $status));
    }

    public function getDeliveryRateAttribute(): float
    {
        return $this->sent_count > 0 ? round(($this->delivered_count / $this->sent_count) * 100, 1) : 0.0;
    }
}
