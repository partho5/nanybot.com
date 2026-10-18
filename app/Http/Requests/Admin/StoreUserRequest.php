<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('username') && $this->input('username') === '') {
            $this->merge(['username' => null]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'username' => ['nullable', 'string', 'max:60', 'regex:/^(?=.*[a-z])[a-z0-9][a-z0-9_-]*$/', Rule::unique(User::class)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['required', 'string', Password::default()],
        ];
    }
}
