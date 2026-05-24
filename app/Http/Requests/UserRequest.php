<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $userId = $this->route('user')?->id;

        $assignableRoles = $this->user()?->isOwner()
            ? ['owner', 'admin', 'manager', 'staff', 'viewer']
            : ['manager', 'staff', 'viewer'];

        return [
            'name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$userId],
            'role' => ['required', Rule::in($assignableRoles)],
            'status' => ['required', 'in:active,inactive,suspended'],
            'password' => [$userId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
