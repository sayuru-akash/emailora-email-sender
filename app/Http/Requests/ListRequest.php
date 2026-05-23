<?php

namespace App\Http\Requests;

use App\Models\ListModel;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ListRequest extends FormRequest
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
        $listId = $this->route('list')?->id;
        $slug = Str::slug((string) $this->input('name'));

        return [
            'name' => [
                'required',
                'string',
                'max:180',
                function (string $attribute, mixed $value, Closure $fail) use ($listId, $slug): void {
                    if (ListModel::query()->where('slug', $slug)->when($listId, fn ($query) => $query->whereKeyNot($listId))->exists()) {
                        $fail('A list with this name already exists.');
                    }
                },
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive,archived'],
            'color' => ['nullable', 'string', 'max:24'],
        ];
    }
}
