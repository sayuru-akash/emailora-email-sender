<?php

namespace App\Services\Activity;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        string $category = 'system',
        string $severity = 'info',
        ?User $user = null,
    ): ActivityLog {
        $request = request();
        $actor = $user ?: Auth::user();

        return ActivityLog::create([
            'category' => $category,
            'event' => $event,
            'severity' => $severity,
            'user_id' => $actor?->id,
            'user_name' => $actor?->name,
            'user_email' => $actor?->email,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'subject_name' => $this->subjectName($subject),
            'description' => $description,
            'properties' => $this->sanitizeProperties($properties),
            'ip_address' => $request instanceof Request ? $request->ip() : null,
            'user_agent' => $request instanceof Request ? $request->userAgent() : null,
            'method' => $request instanceof Request ? $request->method() : null,
            'url' => $request instanceof Request ? $request->getPathInfo() : null,
            'route_name' => $request instanceof Request ? $request->route()?->getName() : null,
            'occurred_at' => now(),
        ]);
    }

    private function subjectName(?Model $subject): ?string
    {
        if (! $subject) {
            return null;
        }

        foreach (['name', 'full_name', 'subject', 'file_name', 'email', 'event_type'] as $attribute) {
            $value = $subject->getAttribute($attribute);
            if (filled($value)) {
                return (string) $value;
            }
        }

        return class_basename($subject).' #'.$subject->getKey();
    }

    private function sanitizeProperties(array $properties): array
    {
        return collect($properties)
            ->reject(fn ($value, $key) => $this->isSensitiveKey((string) $key))
            ->map(fn ($value) => $this->sanitizeValue($value))
            ->all();
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if ($value instanceof Model) {
            return class_basename($value).' #'.$value->getKey();
        }

        if (is_array($value)) {
            return collect($value)
                ->reject(fn ($nested, $key) => $this->isSensitiveKey((string) $key))
                ->map(fn ($nested) => $this->sanitizeValue($nested))
                ->all();
        }

        return $value;
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
