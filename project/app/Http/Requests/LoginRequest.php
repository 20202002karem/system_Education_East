<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // login_identifier = email (product decision). Must be a syntactically
            // valid email; existence/mismatch is never revealed (uniform failure message).
            'login_identifier' => ['required', 'string', 'email', 'max:150'],
            'password' => ['required', 'string'],
        ];
    }
}
