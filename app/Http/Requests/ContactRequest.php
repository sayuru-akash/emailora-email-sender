<?php

namespace App\Http\Requests;

use App\Models\Contact;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
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
        $contactId = $this->route('contact')?->id;

        return [
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'full_name' => ['nullable', 'string', 'max:240'],
            'email' => ['required', 'email', 'max:255', Rule::unique((new Contact)->getTable(), 'email_normalized')->ignore($contactId)],
            'phone' => ['nullable', 'string', 'max:80'],
            'company' => ['nullable', 'string', 'max:180'],
            'job_title' => ['nullable', 'string', 'max:180'],
            'country' => ['nullable', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:active,inactive,unsubscribed,bounced,complained,blocked,invalid'],
            'consent_status' => ['nullable', 'in:unknown,opted_in,opted_out'],
            'notes' => ['nullable', 'string'],
            'list_ids' => ['array'],
            'list_ids.*' => ['integer', 'exists:lists,id'],
            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge(['email' => Contact::normalizeEmail($this->input('email'))]);
        }
    }
}
