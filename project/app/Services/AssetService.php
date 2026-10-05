<?php

namespace App\Services;

use App\Exceptions\DomainConflictException;
use App\Models\Asset;
use App\Models\AssetIdentifierCorrection;
use App\Models\AssetLegacyNumber;
use App\Models\AssetStatusHistory;
use App\Models\User;
use App\Support\AssetIdentifier;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/** M2 business logic (BR-M2-01..08). Controllers stay thin; every write is audited. */
class AssetService
{
    public const INITIAL_STATUS = 'working'; // OI-DB-01 resolved provisionally: server-side default

    public function __construct(protected AuditLogger $audit) {}

    public function create(User $actor, array $data, string $ip): Asset
    {
        $inventory = AssetIdentifier::normalize($data['inventory_no']);
        $serial = AssetIdentifier::normalize($data['serial_no'] ?? null);
        $legacy = [];
        foreach ($data['legacy_numbers'] ?? [] as $n) {
            $legacy[] = AssetIdentifier::normalize($n);
        }

        $this->assertUnique('inventory_no', $inventory);
        if ($serial !== null) {
            $this->assertUnique('serial_no', $serial);
        }

        try {
            return DB::transaction(function () use ($actor, $data, $inventory, $serial, $legacy, $ip) {
                $asset = Asset::create([
                    'inventory_no' => $inventory,
                    'serial_no' => $serial,
                    'category_id' => $data['category_id'],
                    'current_site_id' => $data['current_site_id'],
                    'status' => self::INITIAL_STATUS,
                    'holder_text' => $data['holder_text'] ?? null,
                    'version' => 1,
                ]);
                foreach ($legacy as $n) {
                    AssetLegacyNumber::create([
                        'asset_id' => $asset->id, 'legacy_number' => $n, 'added_by' => $actor->id, 'added_at' => now(),
                    ]);
                }
                $asset->refresh();
                $this->audit->record($actor->id, 'asset.created', 'asset', $asset->id,
                    after: $asset->only(['inventory_no', 'serial_no', 'category_id', 'current_site_id', 'status', 'holder_text']) + ['legacy_numbers' => $legacy],
                    ip: $ip);

                return $asset;
            });
        } catch (QueryException $e) {
            throw $this->mapUnique($e);
        }
    }

    public function update(User $actor, Asset $asset, array $data, string $ip): Asset
    {
        return DB::transaction(function () use ($actor, $asset, $data, $ip) {
            $asset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            $this->assertVersion($asset, (int) $data['version']);

            $changes = [];
            foreach (['category_id', 'holder_text'] as $f) {
                if (array_key_exists($f, $data) && $data[$f] !== $asset->{$f}) {
                    $changes[$f] = $data[$f];
                }
            }
            if ($changes === []) {
                return $asset;
            }
            $before = $asset->only(array_keys($changes));
            $this->bump($asset, $changes);
            $this->audit->record($actor->id, 'asset.updated', 'asset', $asset->id, before: $before, after: $asset->only(array_keys($changes)), ip: $ip);

            return $asset;
        });
    }

    /** @return array{0: Asset, 1: AssetStatusHistory} */
    public function changeStatus(User $actor, Asset $asset, string $to, ?string $reason, int $version, string $ip): array
    {
        return DB::transaction(function () use ($actor, $asset, $to, $reason, $version, $ip) {
            $asset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            $this->assertVersion($asset, $version);

            if (in_array($asset->status, Asset::RESERVED_STATUSES, true)) {
                throw new DomainConflictException('status_locked', 'لا يمكن تغيير حالة هذا الجهاز يدوياً', 422, ['to_status' => ['الحالة الحالية محجوزة']]);
            }
            $from = $asset->status;
            $this->bump($asset, ['status' => $to]);
            $entry = AssetStatusHistory::create([
                'asset_id' => $asset->id, 'from_status' => $from, 'to_status' => $to,
                'changed_by' => $actor->id, 'reason' => $reason, 'changed_at' => now(),
            ]);
            $this->audit->record($actor->id, 'asset.status_changed', 'asset', $asset->id,
                before: ['status' => $from], after: ['status' => $to], reason: $reason, ip: $ip);

            return [$asset, $entry];
        });
    }

    /** @return array{0: Asset, 1: AssetIdentifierCorrection} */
    public function correctIdentifier(User $actor, Asset $asset, string $field, string $newValue, string $reason, string $ip): array
    {
        $new = AssetIdentifier::normalize($newValue);
        try {
            return DB::transaction(function () use ($actor, $asset, $field, $new, $reason, $ip) {
                $asset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
                $old = $asset->{$field};
                if ($old === $new) {
                    throw new DomainConflictException('validation_failed', 'القيمة الجديدة مطابقة للحالية', 422, ['new_value' => ['القيمة الجديدة مطابقة للحالية']]);
                }
                $this->assertUnique($field, $new, $asset->id);
                $this->bump($asset, [$field => $new]);
                $correction = AssetIdentifierCorrection::create([
                    'asset_id' => $asset->id, 'field_name' => $field, 'old_value' => (string) $old,
                    'new_value' => $new, 'corrected_by' => $actor->id, 'reason' => $reason, 'corrected_at' => now(),
                ]);
                $this->audit->record($actor->id, 'asset.identifier_corrected', 'asset', $asset->id,
                    before: [$field => $old], after: [$field => $new], reason: $reason, flags: ['sensitive' => true], ip: $ip);

                return [$asset, $correction];
            });
        } catch (QueryException $e) {
            throw $this->mapUnique($e);
        }
    }

    protected function bump(Asset $asset, array $changes): void
    {
        $asset->fill($changes);
        $asset->version = $asset->version + 1;
        $asset->save();
    }

    protected function assertVersion(Asset $asset, int $version): void
    {
        if ($asset->version !== $version) {
            throw new DomainConflictException('version_conflict', 'تم تعديل الجهاز من قبل مستخدم آخر، أعد تحميل البطاقة');
        }
    }

    protected function assertUnique(string $field, ?string $value, ?int $exceptId = null): void
    {
        $q = Asset::where($field, $value);
        if ($exceptId) {
            $q->where('id', '!=', $exceptId);
        }
        if ($q->exists()) {
            throw $this->duplicate($field);
        }
    }

    protected function duplicate(string $field): DomainConflictException
    {
        return new DomainConflictException(
            "duplicate_{$field}",
            $field === 'inventory_no' ? 'رقم الجرد مستخدم مسبقاً' : 'الرقم التسلسلي مستخدم مسبقاً',
            409, [$field => ['القيمة مكررة']]
        );
    }

    protected function mapUnique(QueryException $e): \Throwable
    {
        $msg = $e->getMessage();
        if (str_contains($msg, 'serial_no')) {
            return $this->duplicate('serial_no');
        }
        if (str_contains($msg, 'inventory_no')) {
            return $this->duplicate('inventory_no');
        }

        return $e;
    }
}
