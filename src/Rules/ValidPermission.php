<?php

namespace Squareetlabs\LaravelTeamsPermissions\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidPermission implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Valid permission format: lowercase letters, numbers, underscores, hyphens, and dots
        // Examples: 'posts.view', 'users.create', 'admin.settings.edit'
        return preg_match('/^[a-z0-9_\-]+(\.[a-z0-9_\-]+)*$/', $value) === 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must be a valid permission code (e.g., posts.view, users.create).';
    }
}

