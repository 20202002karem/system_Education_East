<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** M3 Batch 3 — validation only; authorization/scope are enforced server-side in the service layer. */
class CancelServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason_category' => 'required|string|max:60',
            'reason_text' => 'required|string|max:5000',
            'work_done_summary' => 'nullable|string|max:5000',
            'version' => 'nullable|integer',
        ];
    }
}
