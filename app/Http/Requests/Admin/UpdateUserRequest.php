<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Admins may edit anyone; everyone else only their own profile (and never the role). */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');
        $user = $this->user();

        return $user !== null && $target instanceof User && ($user->isAdmin() || $user->is($target));
    }

    protected function prepareForValidation(): void
    {
        foreach (['username', 'avatar', 'headline', 'bio', 'password'] as $key) {
            if ($this->has($key) && $this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }

        // Drop empty social link rows (blank value).
        if (is_array($this->social_links)) {
            $this->merge([
                'social_links' => array_filter($this->social_links, fn ($url) => filled($url)),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($target->id)],
            'username' => ['nullable', 'string', 'max:60', 'regex:/^(?=.*[a-z])[a-z0-9][a-z0-9_-]*$/', Rule::unique(User::class)->ignore($target->id)],
            'avatar' => ['nullable', 'url:https', 'max:2048'],
            'headline' => ['nullable', 'string', 'max:160'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'social_links' => ['nullable', 'array', 'max:10'],
            // Keys are rendered as link labels, so keep them to plain label characters.
            'social_links.*' => ['string', 'url:https', 'max:2048'],
            // Admin-only fields; silently ignored for non-admins by the controller.
            'role' => ['sometimes', Rule::enum(UserRole::class)],
            'password' => ['nullable', 'string', Password::default()],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            foreach (array_keys((array) $this->input('social_links', [])) as $label) {
                if (! is_string($label) || ! preg_match('/^[\p{L}\p{N} ._-]{1,30}$/u', $label)) {
                    $validator->errors()->add('social_links', 'Social link labels may only contain letters, numbers, spaces, dots, dashes and underscores (max 30).');

                    break;
                }
            }

            $target = $this->route('user');

            // An admin may not demote themselves (avoids locking everyone out).
            if ($this->user()->isAdmin() && $this->user()->is($target)
                && $this->has('role') && $this->input('role') !== UserRole::Admin->value) {
                $validator->errors()->add('role', 'You cannot remove your own admin role.');
            }
        }];
    }
}
