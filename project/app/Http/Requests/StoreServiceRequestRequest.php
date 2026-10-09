<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** M3 Batch 3 — validation only; authorization/scope are enforced server-side in the service layer. */
class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origin_site_id' => 'required|integer',
            'asset_id' => 'nullable|integer',
            'channel_id' => 'required|integer',
            'description' => 'required|string|max:5000',
            'suggested_priority' => 'nullable|in:emergency,high,normal,low',
            'requester_name' => 'nullable|string|max:150',
        ];
    }
}
