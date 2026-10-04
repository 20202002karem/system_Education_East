<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MfaVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mfa_challenge_token' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
        ];
    }
}
