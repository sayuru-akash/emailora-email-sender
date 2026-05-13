<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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

        return [
            'name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$userId],
            'role' => ['required', 'in:owner,admin,manager,staff,viewer'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'password' => [$userId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
