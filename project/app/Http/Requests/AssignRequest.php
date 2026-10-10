<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** M3 Batch 3 — validation only; authorization/scope are enforced server-side in the service layer. */
class AssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignee_id' => 'required|integer',
            'reason' => 'nullable|string|max:255',
            'version' => 'nullable|integer',
        ];
    }
}
