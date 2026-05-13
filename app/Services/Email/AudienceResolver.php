<?php

namespace App\Services\Email;

use App\Models\Contact;
use App\Models\EmailCampaign;
use Illuminate\Database\Eloquent\Builder;

final class AudienceResolver
{
    public function queryForCampaign(EmailCampaign $campaign): Builder
    {
        $query = Contact::query()->emailable();
        $filters = $campaign->target_filters ?? [];

        return match ($campaign->target_type) {
            'list' => $query->whereHas('lists', fn ($query) => $query->whereIn('lists.id', $filters['list_ids'] ?? [])),
            'tag' => $query->whereHas('tags', fn ($query) => $query->whereIn('tags.id', $filters['tag_ids'] ?? [])),
            'manual_selection' => $query->whereIn('id', $filters['contact_ids'] ?? []),
            default => $query,
        };
    }
}
