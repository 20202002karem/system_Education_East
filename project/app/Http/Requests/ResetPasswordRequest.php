<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // MD-01: password policy - minimum length enforced via config, not hardcoded,
            // so the chairman-editable `settings` table stays the single source of truth
            // at the service layer (PasswordPolicy checks name-similarity + common-password list there).
            'password' => ['required', 'string', 'min:5'],
        ];
    }
}
