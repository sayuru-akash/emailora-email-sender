<?php

namespace App\Observers;

use App\Services\Activity\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ActivityLogObserver
{
    public function created(Model $model): void
    {
        $this->logger()->log(
            event: class_basename($model).'.created',
            description: class_basename($model).' was created.',
            subject: $model,
            properties: ['summary' => $this->safeAttributes($model, $model->getAttributes())],
            category: $this->category($model),
        );
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), ['updated_at']);
        if ($changes === []) {
            return;
        }

        $this->logger()->log(
            event: class_basename($model).'.updated',
            description: class_basename($model).' was updated.',
            subject: $model,
            properties: [
                'changes' => $this->safeAttributes($model, $changes),
                'previous' => $this->safeAttributes($model, Arr::only($model->getOriginal(), array_keys($changes))),
            ],
            category: $this->category($model),
        );
    }

    public function deleted(Model $model): void
    {
        $this->logger()->log(
            event: class_basename($model).'.deleted',
            description: class_basename($model).' was deleted.',
            subject: $model,
            properties: ['summary' => $this->safeAttributes($model, $model->getAttributes())],
            category: $this->category($model),
            severity: 'warning',
        );
    }

    private function logger(): ActivityLogger
    {
        return app(ActivityLogger::class);
    }

    private function category(Model $model): string
    {
        return match (class_basename($model)) {
            'Contact', 'ListModel', 'Tag', 'EmailSuppression' => 'contacts',
            'EmailCampaign', 'CampaignRecipient', 'EmailMessage', 'EmailEvent' => 'campaigns',
            'EmailTemplate' => 'templates',
            'ContactImport', 'ImportRow' => 'imports',
            'User' => 'users',
            'SystemSetting' => 'settings',
            default => 'system',
        };
    }

    private function safeAttributes(Model $model, array $attributes): array
    {
        $allowed = match (class_basename($model)) {
            'Contact' => ['id', 'uuid', 'email_normalized', 'status', 'source', 'consent_status', 'created_by', 'updated_by', 'created_at', 'updated_at'],
            'ListModel', 'Tag' => ['id', 'uuid', 'name', 'slug', 'status', 'color', 'created_by', 'updated_at'],
            'EmailCampaign' => ['id', 'uuid', 'name', 'subject', 'status', 'target_type', 'scheduled_at', 'recipient_mode', 'total_recipients', 'queued_count', 'sent_count', 'failed_count', 'created_by', 'updated_at'],
            'CampaignRecipient' => ['id', 'email_campaign_id', 'contact_id', 'email_normalized', 'status', 'sent_at', 'failed_at', 'skip_reason', 'updated_at'],
            'EmailMessage' => ['id', 'email_campaign_id', 'contact_id', 'campaign_recipient_id', 'provider', 'provider_message_id', 'status', 'sent_at', 'delivered_at', 'failed_at', 'updated_at'],
            'EmailEvent' => ['id', 'email_message_id', 'email_campaign_id', 'contact_id', 'provider', 'event_type', 'email_normalized', 'occurred_at', 'created_at'],
            'EmailTemplate' => ['id', 'uuid', 'name', 'subject', 'status', 'category', 'created_by', 'updated_at'],
            'ContactImport' => ['id', 'uuid', 'file_name', 'file_type', 'status', 'duplicate_handling', 'total_rows', 'processed_rows', 'successful_rows', 'failed_rows', 'duplicate_rows', 'uploaded_by', 'started_at', 'completed_at', 'created_at', 'updated_at'],
            'ImportRow' => ['id', 'contact_import_id', 'row_number', 'status', 'email_normalized', 'contact_id', 'created_at', 'updated_at'],
            'User' => ['id', 'name', 'email', 'role', 'status', 'last_login_at', 'created_at', 'updated_at'],
            'SystemSetting' => ['id', 'key', 'group', 'type', 'updated_at'],
            default => ['id', 'uuid', 'name', 'status', 'created_at', 'updated_at'],
        };

        return collect($attributes)
            ->only($allowed)
            ->reject(fn ($value, $key) => $this->isSensitiveKey((string) $key))
            ->map(fn ($value) => is_string($value) && Str::length($value) > 500 ? Str::limit($value, 500) : $value)
            ->all();
    }

    private function isSensitiveKey(string $key): bool
    {
        return str_contains(strtolower($key), 'password')
            || str_contains(strtolower($key), 'token')
            || str_contains(strtolower($key), 'secret')
            || str_contains(strtolower($key), 'api_key')
            || str_contains(strtolower($key), 'authorization')
            || str_contains(strtolower($key), 'signature')
            || str_contains(strtolower($key), 'cookie')
            || in_array(strtolower($key), ['payload', 'provider_response', 'raw_data', 'mapped_data', 'html_body', 'text_body', 'personalized_html', 'personalized_text', 'metadata', 'headers'], true);
    }
}
