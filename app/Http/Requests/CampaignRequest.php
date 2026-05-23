<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:180'],
            'preheader' => ['nullable', 'string', 'max:180'],
            'from_name' => ['required', 'string', 'max:120'],
            'from_email' => ['required', 'email', 'max:255'],
            'reply_to_email' => ['nullable', 'email', 'max:255'],
            'html_body' => ['required_without:text_body', 'nullable', 'string'],
            'text_body' => ['required_without:html_body', 'nullable', 'string'],
            'email_template_id' => ['nullable', 'exists:email_templates,id'],
            'provider' => ['nullable', 'in:resend,brevo,auto'],
            'target_type' => ['required', 'in:all_contacts,list,tag,saved_segment,manual_selection,advanced_filter'],
            'target_filters' => ['nullable', 'array'],
            'status' => ['nullable', 'in:draft,scheduled'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
