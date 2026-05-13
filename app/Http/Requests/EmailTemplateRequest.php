<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EmailTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isActive() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'category' => ['nullable', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:180'],
            'preheader' => ['nullable', 'string', 'max:180'],
            'html_body' => ['required_without:text_body', 'nullable', 'string'],
            'text_body' => ['required_without:html_body', 'nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
