<?php

namespace App\Http\Requests;

use App\Models\Tag;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class TagRequest extends FormRequest
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
        $tagId = $this->route('tag')?->id;
        $slug = Str::slug((string) $this->input('name'));

        return [
            'name' => [
                'required',
                'string',
                'max:180',
                function (string $attribute, mixed $value, Closure $fail) use ($tagId, $slug): void {
                    if (Tag::query()->where('slug', $slug)->when($tagId, fn ($query) => $query->whereKeyNot($tagId))->exists()) {
                        $fail('A tag with this name already exists.');
                    }
                },
            ],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:24'],
        ];
    }
}
