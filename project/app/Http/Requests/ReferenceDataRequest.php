<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared request for the four reference lists (device-categories, request-types,
 * task-types, intake-channels). device_categories only has `name` per Batch 2
 * §B12 (Appendices v1.0 binding version); the other three additionally accept
 * is_active (or, for task-types, is_administrative/secretary_assignable) —
 * the controller only pulls the fields relevant to the resource being written.
 */
class ReferenceDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
            'is_administrative' => ['sometimes', 'boolean'],
            'secretary_assignable' => ['sometimes', 'boolean'],
        ];
    }
}
