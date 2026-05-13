<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:180'],
            'timezone' => ['required', 'timezone'],
            'default_from_name' => ['nullable', 'string', 'max:120'],
            'default_from_email' => ['nullable', 'email', 'max:255'],
            'default_reply_to' => ['nullable', 'email', 'max:255'],
            'default_provider' => ['required', 'in:resend,brevo,auto'],
            'fallback_provider' => ['nullable', 'in:resend,brevo'],
            'rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:100000'],
            'chunk_size' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
