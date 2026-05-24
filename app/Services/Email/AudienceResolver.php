<?php

namespace App\Services\Email;

use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\SavedSegment;
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
            'saved_segment' => $this->queryForSavedSegment($query, $filters),
            'advanced_filter' => $this->applyFilters($query, $filters),
            'manual_selection' => $query->whereIn('id', $filters['contact_ids'] ?? []),
            default => $query,
        };
    }

    public function queryForFilters(array $filters): Builder
    {
        return $this->applyFilters(Contact::query()->emailable(), $filters);
    }

    private function queryForSavedSegment(Builder $query, array $filters): Builder
    {
        $segmentId = $filters['segment_id'] ?? $filters['saved_segment_id'] ?? null;
        $segment = $segmentId ? SavedSegment::query()->where('status', 'active')->find($segmentId) : null;

        if (! $segment) {
            return $query->whereRaw('1 = 0');
        }

        return $this->applyFilters($query, $segment->filters ?? []);
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        $listIds = $this->integerList($filters['list_ids'] ?? $filters['lists'] ?? []);
        $tagIds = $this->integerList($filters['tag_ids'] ?? $filters['tags'] ?? []);
        $contactIds = $this->integerList($filters['contact_ids'] ?? []);

        return $query
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['source'] ?? null, fn (Builder $query, string $source) => $query->where('source', $source))
            ->when($filters['country'] ?? null, fn (Builder $query, string $country) => $query->where('country', $country))
            ->when($filters['district'] ?? null, fn (Builder $query, string $district) => $query->where('district', $district))
            ->when($filters['city'] ?? null, fn (Builder $query, string $city) => $query->where('city', $city))
            ->when($filters['company'] ?? null, fn (Builder $query, string $company) => $query->where('company', $company))
            ->when($filters['consent_status'] ?? null, fn (Builder $query, string $consentStatus) => $query->where('consent_status', $consentStatus))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->search($search))
            ->when($listIds !== [], fn (Builder $query) => $query->whereHas('lists', fn (Builder $query) => $query->whereIn('lists.id', $listIds)))
            ->when($tagIds !== [], fn (Builder $query) => $query->whereHas('tags', fn (Builder $query) => $query->whereIn('tags.id', $tagIds)))
            ->when($contactIds !== [], fn (Builder $query) => $query->whereIn('id', $contactIds));
    }

    private function integerList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return collect($values)->map(fn ($value) => (int) $value)->filter()->values()->all();
    }
}
