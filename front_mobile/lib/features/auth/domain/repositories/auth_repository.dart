import '../../../users/domain/entities/user.dart';

/// Result of POST /auth/login (Batch 3 §2.1/§3.1) — either a full session or
/// an MFA challenge (chairman only, AUTH-07).

/// Domain-layer contract — presentation (AuthCubit) depends on this
/// interface only, never on Dio/ApiClient directly (Clean Architecture).
abstract class AuthRepository {
  Future<void> login(
      {required String loginIdentifier, required String password});
  Future<void> logout();
  Future<UserEntity> getCurrentUser();
  Future<bool> hasStoredSession();
}
