<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Enforces the system password policy: minimum length of 11 characters,
 * at least one uppercase letter, one lowercase letter, one number, and
 * at least two special characters. Any character set is otherwise allowed
 * (unicode letters, spaces, punctuation, etc.) — inputs are not restricted
 * to alphanumeric-only characters.
 */
class StrongPassword implements ValidationRule
{
    public function __construct(
        protected int $minLength = 11,
        protected int $minSpecialChars = 2,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        if (mb_strlen($value) < $this->minLength) {
            $fail("The :attribute must be at least {$this->minLength} characters long.");

            return;
        }

        if (! preg_match('/\p{Lu}/u', $value)) {
            $fail('The :attribute must contain at least one uppercase letter.');

            return;
        }

        if (! preg_match('/\p{Ll}/u', $value)) {
            $fail('The :attribute must contain at least one lowercase letter.');

            return;
        }

        if (! preg_match('/\p{N}/u', $value)) {
            $fail('The :attribute must contain at least one number.');

            return;
        }

        // "Special" = anything that isn't a letter or a digit, so the rule
        // doesn't box users into a fixed whitelist of symbols.
        $specialCharCount = preg_match_all('/[^\p{L}\p{N}]/u', $value);

        if ($specialCharCount < $this->minSpecialChars) {
            $fail("The :attribute must contain at least {$this->minSpecialChars} special characters (e.g. \$ and ?).");
        }
    }
}
