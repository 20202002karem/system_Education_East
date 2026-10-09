<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** M3 Batch 3 — validation only; authorization/scope are enforced server-side in the service layer. */
class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'task_type_id' => 'required|integer',
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:5000',
            'request_cycle_id' => 'nullable|integer',
            'asset_id' => 'nullable|integer',
            'site_id' => 'nullable|integer',
            'due_at' => 'nullable|date',
        ];
    }
}
