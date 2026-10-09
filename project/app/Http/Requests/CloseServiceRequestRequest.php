<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** M3 Batch 3 — validation only; authorization/scope are enforced server-side in the service layer. */
class CloseServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'closure_action' => 'required|string|max:255',
            'closure_result' => 'required|string|max:255',
            'closure_effort_minutes' => 'required|integer|min:0',
            'version' => 'nullable|integer',
        ];
    }
}
