<?php

namespace App\Http\Requests\Admin;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('slug') && $this->input('slug') === '') {
            $this->merge(['slug' => null]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tag = $this->route('tag');
        $ignore = $tag instanceof Tag ? $tag->id : null;

        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('tags', 'name')->ignore($ignore)],
            'slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('tags', 'slug')->ignore($ignore)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
