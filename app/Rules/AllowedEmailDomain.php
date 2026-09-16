<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class AllowedEmailDomain implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = Str::lower((string) config('auth.allowed_email_domain'));
        $email = Str::lower(trim((string) $value));

        if ($domain === '' || ! Str::endsWith($email, '@'.$domain)) {
            $fail('El correo debe pertenecer al dominio @'.$domain.'.');
        }
    }
}
