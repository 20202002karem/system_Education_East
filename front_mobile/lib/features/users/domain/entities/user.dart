import 'package:equatable/equatable.dart';

/// The single User domain model shared by the Auth feature (current user)
/// and the Users feature (management) — instructions §9: Web and Flutter,
/// and every feature within Flutter, must share one User concept.
enum UserRole { schoolManager, chairman, secretary, engineer, technician }

enum ViewScope { ownSite, sites, all, assignedOnly }

enum UserStatus { active, disabled }

UserRole userRoleFromWire(String value) => switch (value) {
      'school_manager' => UserRole.schoolManager,
      'chairman' => UserRole.chairman,
      'secretary' => UserRole.secretary,
      'engineer' => UserRole.engineer,
      'technician' => UserRole.technician,
      _ => throw ArgumentError('Unknown role: $value'),
    };

String userRoleToWire(UserRole role) => switch (role) {
      UserRole.schoolManager => 'school_manager',
      UserRole.chairman => 'chairman',
      UserRole.secretary => 'secretary',
      UserRole.engineer => 'engineer',
      UserRole.technician => 'technician',
    };

ViewScope viewScopeFromWire(String value) => switch (value) {
      'own_site' => ViewScope.ownSite,
      'sites' => ViewScope.sites,
      'all' => ViewScope.all,
      'assigned_only' => ViewScope.assignedOnly,
      _ => throw ArgumentError('Unknown view_scope: $value'),
    };

String viewScopeToWire(ViewScope scope) => switch (scope) {
      ViewScope.ownSite => 'own_site',
      ViewScope.sites => 'sites',
      ViewScope.all => 'all',
      ViewScope.assignedOnly => 'assigned_only',
    };

/// NOTE: there is intentionally NO mfa_secret field here or anywhere else in
/// the app (instructions §6/§16) — the backend never returns it and the
/// frontend must never model, log, or display it.
class UserEntity extends Equatable {
  const UserEntity({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    required this.viewScope,
    required this.status,
    required this.mfaEnabled,
    this.specialization,
    this.createdAt,
  });

  final int id;
  final String name;
  final String email;
  final UserRole role;
  final ViewScope viewScope;
  final UserStatus status;
  final bool mfaEnabled;
  final String? specialization;
  final DateTime? createdAt;

  @override
  List<Object?> get props => [id, name, email, role, viewScope, status, mfaEnabled, specialization, createdAt];
}
