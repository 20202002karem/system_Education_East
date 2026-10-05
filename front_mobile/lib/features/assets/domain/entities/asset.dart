import 'package:equatable/equatable.dart';

/// M2 Batch 2 §13: six stored values; only the first four are settable in M2
/// (in_transfer / decommissioned are reserved for M4).
enum AssetStatus { working, underMaintenance, broken, stored, inTransfer, decommissioned }

const manualAssetStatuses = [AssetStatus.working, AssetStatus.underMaintenance, AssetStatus.broken, AssetStatus.stored];

AssetStatus assetStatusFromWire(String v) => switch (v) {
      'working' => AssetStatus.working,
      'under_maintenance' => AssetStatus.underMaintenance,
      'broken' => AssetStatus.broken,
      'stored' => AssetStatus.stored,
      'in_transfer' => AssetStatus.inTransfer,
      'decommissioned' => AssetStatus.decommissioned,
      _ => throw ArgumentError('Unknown asset status: $v'),
    };

String assetStatusToWire(AssetStatus s) => switch (s) {
      AssetStatus.working => 'working',
      AssetStatus.underMaintenance => 'under_maintenance',
      AssetStatus.broken => 'broken',
      AssetStatus.stored => 'stored',
      AssetStatus.inTransfer => 'in_transfer',
      AssetStatus.decommissioned => 'decommissioned',
    };

String assetStatusLabel(AssetStatus s) => switch (s) {
      AssetStatus.working => 'يعمل',
      AssetStatus.underMaintenance => 'قيد الصيانة',
      AssetStatus.broken => 'معطّل',
      AssetStatus.stored => 'مخزّن',
      AssetStatus.inTransfer => 'قيد النقل',
      AssetStatus.decommissioned => 'خارج الخدمة',
    };

class AssetEntity extends Equatable {
  const AssetEntity({
    required this.id,
    required this.inventoryNo,
    required this.serialNo,
    required this.categoryId,
    required this.currentSiteId,
    required this.status,
    required this.holderText,
    required this.version,
    required this.updatedAt,
  });

  final int id;
  final String inventoryNo;
  final String? serialNo;
  final int categoryId;
  final int currentSiteId;
  final AssetStatus status;
  final String? holderText;
  final int version;
  final DateTime? updatedAt;

  @override
  List<Object?> get props => [id, inventoryNo, serialNo, categoryId, currentSiteId, status, holderText, version, updatedAt];
}

class AssetStatusEntry extends Equatable {
  const AssetStatusEntry({required this.id, required this.fromStatus, required this.toStatus, required this.changedBy, required this.reason, required this.changedAt});
  final int id;
  final AssetStatus fromStatus;
  final AssetStatus toStatus;
  final int changedBy;
  final String? reason;
  final DateTime? changedAt;
  @override
  List<Object?> get props => [id, fromStatus, toStatus, changedBy, reason, changedAt];
}

enum IdentifierField { inventoryNo, serialNo }

String identifierFieldToWire(IdentifierField f) => f == IdentifierField.inventoryNo ? 'inventory_no' : 'serial_no';
String identifierFieldLabel(IdentifierField f) => f == IdentifierField.inventoryNo ? 'رقم الجرد' : 'الرقم التسلسلي';

class AssetCorrection extends Equatable {
  const AssetCorrection({required this.id, required this.field, required this.oldValue, required this.newValue, required this.correctedBy, required this.reason, required this.correctedAt});
  final int id;
  final IdentifierField field;
  final String oldValue;
  final String newValue;
  final int correctedBy;
  final String reason;
  final DateTime? correctedAt;
  @override
  List<Object?> get props => [id, field, oldValue, newValue, correctedBy, reason, correctedAt];
}

class AssetLegacyNumber extends Equatable {
  const AssetLegacyNumber({required this.id, required this.legacyNumber, required this.source, required this.addedBy, required this.addedAt});
  final int id;
  final String legacyNumber;
  final String? source;
  final int addedBy;
  final DateTime? addedAt;
  @override
  List<Object?> get props => [id, legacyNumber, source, addedBy, addedAt];
}
