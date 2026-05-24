<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mapping' => ['required', 'array'],
            'mapping.email' => ['required', 'string'],
            'mapping.first_name' => ['nullable', 'string'],
            'mapping.last_name' => ['nullable', 'string'],
            'mapping.full_name' => ['nullable', 'string'],
            'mapping.phone' => ['nullable', 'string'],
            'mapping.company' => ['nullable', 'string'],
            'mapping.job_title' => ['nullable', 'string'],
            'mapping.country' => ['nullable', 'string'],
            'mapping.district' => ['nullable', 'string'],
            'mapping.city' => ['nullable', 'string'],
            'mapping.source' => ['nullable', 'string'],
            'mapping.consent_status' => ['nullable', 'string'],
            'mapping.notes' => ['nullable', 'string'],
        ];
    }
}
