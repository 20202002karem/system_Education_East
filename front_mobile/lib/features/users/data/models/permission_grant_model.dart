import '../../domain/entities/permission_grant.dart';

class PermissionGrantModel {
  static PermissionGrantEntity fromJson(Map<String, dynamic> json) {
    return PermissionGrantEntity(
      id: json['id'] as int,
      userId: json['user_id'] as int,
      permissionKey: permissionKeyFromWire(json['permission_key'] as String),
      grantedBy: json['granted_by'] as int,
      grantedAt: DateTime.parse(json['granted_at'] as String),
      revokedBy: json['revoked_by'] as int?,
      revokedAt: json['revoked_at'] != null ? DateTime.parse(json['revoked_at'] as String) : null,
      reason: json['reason'] as String?,
    );
  }
}
