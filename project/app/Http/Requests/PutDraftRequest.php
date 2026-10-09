<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** M3 Batch 3 — validation only; authorization/scope are enforced server-side in the service layer. */
class PutDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'form_type' => 'required|in:request_create,note,closure,cancellation,proposal',
            'form_key' => 'nullable|string|max:60',
            'payload' => 'required|array',
        ];
    }
}
