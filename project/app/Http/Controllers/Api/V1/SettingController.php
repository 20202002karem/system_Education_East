<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use App\Models\SettingHistory;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\DB;

/**
 * Batch 3 §2.4 / §3.6. Every PATCH writes a settings_history row automatically.
 */
class SettingController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function index()
    {
        return ApiResponse::ok(Setting::all());
    }

    public function update(UpdateSettingRequest $request, string $key)
    {
        $actor = $request->user();
        $setting = Setting::find($key);
        $oldValue = $setting?->value;
        $newValue = $request->input('value');

        DB::transaction(function () use ($key, $newValue, $oldValue, $actor) {
            Setting::updateOrCreate(['key' => $key], ['value' => $newValue, 'updated_by' => $actor->id]);

            SettingHistory::create([
                'key' => $key,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'updated_by' => $actor->id,
                'changed_at' => now('UTC'),
            ]);
        });

        $this->audit->record(
            $actor->id, 'setting.updated', 'setting', $key,
            before: ['value' => $oldValue], after: ['value' => $newValue]
        );

        return ApiResponse::ok(Setting::find($key));
    }
}
