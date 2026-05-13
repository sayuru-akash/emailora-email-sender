<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRow extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'raw_data' => 'array',
        'mapped_data' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(ContactImport::class, 'contact_import_id');
    }
}
