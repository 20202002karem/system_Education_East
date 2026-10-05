import '../../domain/entities/asset.dart';

DateTime? _dt(Object? v) => v == null ? null : DateTime.parse(v as String).toLocal();

class AssetModel {
  static AssetEntity fromJson(Map<String, dynamic> j) => AssetEntity(
        id: j['id'] as int,
        inventoryNo: j['inventory_no'] as String,
        serialNo: j['serial_no'] as String?,
        categoryId: j['category_id'] as int,
        currentSiteId: j['current_site_id'] as int,
        status: assetStatusFromWire(j['status'] as String),
        holderText: j['holder_text'] as String?,
        version: (j['version'] as int?) ?? 1,
        updatedAt: _dt(j['updated_at']),
      );
}

class AssetStatusEntryModel {
  static AssetStatusEntry fromJson(Map<String, dynamic> j) => AssetStatusEntry(
        id: j['id'] as int,
        fromStatus: assetStatusFromWire(j['from_status'] as String),
        toStatus: assetStatusFromWire(j['to_status'] as String),
        changedBy: j['changed_by'] as int,
        reason: j['reason'] as String?,
        changedAt: _dt(j['changed_at']),
      );
}

class AssetCorrectionModel {
  static AssetCorrection fromJson(Map<String, dynamic> j) => AssetCorrection(
        id: j['id'] as int,
        field: j['field_name'] == 'inventory_no' ? IdentifierField.inventoryNo : IdentifierField.serialNo,
        oldValue: j['old_value'] as String,
        newValue: j['new_value'] as String,
        correctedBy: j['corrected_by'] as int,
        reason: j['reason'] as String,
        correctedAt: _dt(j['corrected_at']),
      );
}

class AssetLegacyNumberModel {
  static AssetLegacyNumber fromJson(Map<String, dynamic> j) => AssetLegacyNumber(
        id: j['id'] as int,
        legacyNumber: j['legacy_number'] as String,
        source: j['source'] as String?,
        addedBy: j['added_by'] as int,
        addedAt: _dt(j['added_at']),
      );
}
