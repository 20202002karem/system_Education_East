<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** M3 Batch 3 — validation only; authorization/scope are enforced server-side in the service layer. */
class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visibility' => 'required|in:internal,external',
            'body' => 'required|string|max:5000',
        ];
    }
}
