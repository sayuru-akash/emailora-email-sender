<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'consent_at' => 'datetime',
        'last_contacted_at' => 'datetime',
        'last_opened_at' => 'datetime',
        'last_clicked_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'bounced_at' => 'datetime',
        'complained_at' => 'datetime',
        'blocked_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Contact $contact): void {
            $contact->uuid ??= (string) Str::uuid();
            $contact->email_normalized = static::normalizeEmail($contact->email_normalized ?: $contact->email);
        });

        static::saving(function (Contact $contact): void {
            $contact->email_normalized = static::normalizeEmail($contact->email_normalized ?: $contact->email);
        });
    }

    public static function normalizeEmail(?string $email): string
    {
        return Str::lower(trim((string) $email));
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(ListModel::class, 'contact_list', 'contact_id', 'list_id')->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
            $query->where('full_name', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('company', 'like', "%{$search}%");
        }));
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeEmailable($query)
    {
        return $query->where('status', 'active')->whereNotIn('email_normalized', EmailSuppression::query()->select('email_normalized'));
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name ?: trim("{$this->first_name} {$this->last_name}") ?: $this->company ?: $this->email;
    }
}
