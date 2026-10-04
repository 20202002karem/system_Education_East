import 'package:equatable/equatable.dart';

enum PermissionKey { initiateTransfer, initiateDecommission, editAssets }

PermissionKey permissionKeyFromWire(String value) => switch (value) {
      'initiate_transfer' => PermissionKey.initiateTransfer,
      'initiate_decommission' => PermissionKey.initiateDecommission,
      'edit_assets' => PermissionKey.editAssets,
      _ => throw ArgumentError('Unknown permission_key: $value'),
    };

String permissionKeyToWire(PermissionKey key) => switch (key) {
      PermissionKey.initiateTransfer => 'initiate_transfer',
      PermissionKey.initiateDecommission => 'initiate_decommission',
      PermissionKey.editAssets => 'edit_assets',
    };

/// AUTH-08 (Batch 2 §B5). create_request is intentionally absent from
/// PermissionKey (D-22b reserved/disabled) — there is no wire value for it.
class PermissionGrantEntity extends Equatable {
  const PermissionGrantEntity({
    required this.id,
    required this.userId,
    required this.permissionKey,
    required this.grantedBy,
    required this.grantedAt,
    this.revokedBy,
    this.revokedAt,
    this.reason,
  });

  final int id;
  final int userId;
  final PermissionKey permissionKey;
  final int grantedBy;
  final DateTime grantedAt;
  final int? revokedBy;
  final DateTime? revokedAt;
  final String? reason;

  bool get isActive => revokedAt == null;

  @override
  List<Object?> get props => [id, userId, permissionKey, grantedBy, grantedAt, revokedBy, revokedAt, reason];
}
