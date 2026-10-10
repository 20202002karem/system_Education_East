<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** M3 Batch 3 — validation only; authorization/scope are enforced server-side in the service layer. */
class ActionVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => 'nullable|integer',
        ];
    }
}
