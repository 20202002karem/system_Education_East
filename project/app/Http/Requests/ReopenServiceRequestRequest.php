<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** M3 Batch 3 — validation only; authorization/scope are enforced server-side in the service layer. */
class ReopenServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:2000',
        ];
    }
}
