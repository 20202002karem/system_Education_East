import '../../domain/entities/user.dart';

/// Wire (de)serialization for UserEntity. Kept in `data/` — domain never
/// imports JSON concerns (Clean Architecture boundary, instructions §5).
class UserModel {
  static UserEntity fromJson(Map<String, dynamic> json) {
    return UserEntity(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      role: userRoleFromWire(json['role'] as String),
      viewScope: viewScopeFromWire(json['view_scope'] as String),
      status: json['status'] == 'active' ? UserStatus.active : UserStatus.disabled,
      mfaEnabled: json['mfa_enabled'] as bool? ?? false,
      specialization: json['specialization'] as String?,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'] as String) : null,
    );
  }
}
