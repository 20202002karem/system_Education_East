import '../../../../core/utils/paginated.dart';
import '../../../organization/domain/entities/site.dart';
import '../entities/permission_grant.dart';
import '../entities/user.dart';

/// Batch 3 §2.2 — Users & Sessions, chairman-only on the backend. This
/// interface is the ONLY thing UsersCubit/UserDetailCubit depend on.
abstract class UsersRepository {
  Future<Paginated<UserEntity>> list({String? role, String? status, int page = 1, int perPage = 20});
  Future<UserEntity> get(int id);
  Future<UserEntity> create({
    required String name,
    required String loginIdentifier,
    required UserRole role,
    required ViewScope viewScope,
    String? specialization,
    List<int>? siteScopeIds,
  });
  Future<UserEntity> update(int id, {String? name, UserRole? role, ViewScope? viewScope, String? specialization, List<int>? siteScopeIds});
  Future<UserEntity> disable(int id);
  Future<UserEntity> enable(int id);
  Future<void> resetPassword(int id, String password);
  Future<void> terminateSessions(int id);

  Future<List<PermissionGrantEntity>> permissionGrants(int userId);
  Future<PermissionGrantEntity> grantPermission(int userId, {required PermissionKey key, String? reason});
  Future<PermissionGrantEntity> revokePermission(int grantId, {String? reason});

  Future<List<SiteEntity>> siteScopes(int userId);
  Future<List<SiteEntity>> replaceSiteScopes(int userId, List<int> siteIds);
}
