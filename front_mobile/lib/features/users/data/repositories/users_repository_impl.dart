import '../../../../core/utils/paginated.dart';
import '../../../organization/data/models/site_model.dart';
import '../../../organization/domain/entities/site.dart';
import '../../domain/entities/permission_grant.dart';
import '../../domain/entities/user.dart';
import '../../domain/repositories/users_repository.dart';
import '../datasources/users_remote_datasource.dart';
import '../models/permission_grant_model.dart';
import '../models/user_model.dart';

class UsersRepositoryImpl implements UsersRepository {
  UsersRepositoryImpl(this._remote);
  final UsersRemoteDataSource _remote;

  @override
  Future<Paginated<UserEntity>> list({String? role, String? status, int page = 1, int perPage = 20}) async {
    final result = await _remote.list(role: role, status: status, page: page, perPage: perPage);
    return Paginated(
      items: result.data.map((e) => UserModel.fromJson(e as Map<String, dynamic>)).toList(),
      page: result.meta['page'] as int,
      perPage: result.meta['per_page'] as int,
      total: result.meta['total'] as int,
    );
  }

  @override
  Future<UserEntity> get(int id) async => UserModel.fromJson(await _remote.get(id));

  @override
  Future<UserEntity> create({
    required String name,
    required String loginIdentifier,
    required UserRole role,
    required ViewScope viewScope,
    String? specialization,
    List<int>? siteScopeIds,
  }) async {
    final json = await _remote.create({
      'name': name,
      'login_identifier': loginIdentifier,
      'role': userRoleToWire(role),
      'view_scope': viewScopeToWire(viewScope),
      if (specialization != null) 'specialization': specialization,
      if (viewScope == ViewScope.sites) 'site_scope_ids': siteScopeIds ?? const [],
    });
    return UserModel.fromJson(json);
  }

  @override
  Future<UserEntity> update(int id, {String? name, UserRole? role, ViewScope? viewScope, String? specialization, List<int>? siteScopeIds}) async {
    final json = await _remote.update(id, {
      if (name != null) 'name': name,
      if (role != null) 'role': userRoleToWire(role),
      if (viewScope != null) 'view_scope': viewScopeToWire(viewScope),
      if (specialization != null) 'specialization': specialization,
      if (viewScope == ViewScope.sites && siteScopeIds != null) 'site_scope_ids': siteScopeIds,
    });
    return UserModel.fromJson(json);
  }

  @override
  Future<UserEntity> disable(int id) async => UserModel.fromJson(await _remote.disable(id));

  @override
  Future<UserEntity> enable(int id) async => UserModel.fromJson(await _remote.enable(id));

  @override
  Future<void> resetPassword(int id, String password) => _remote.resetPassword(id, password);

  @override
  Future<void> terminateSessions(int id) => _remote.terminateSessions(id);

  @override
  Future<List<PermissionGrantEntity>> permissionGrants(int userId) async {
    final list = await _remote.permissionGrants(userId);
    return list.map((e) => PermissionGrantModel.fromJson(e as Map<String, dynamic>)).toList();
  }

  @override
  Future<PermissionGrantEntity> grantPermission(int userId, {required PermissionKey key, String? reason}) async {
    final json = await _remote.grantPermission(userId, {'permission_key': permissionKeyToWire(key), if (reason != null) 'reason': reason});
    return PermissionGrantModel.fromJson(json);
  }

  @override
  Future<PermissionGrantEntity> revokePermission(int grantId, {String? reason}) async {
    final json = await _remote.revokePermission(grantId, reason: reason);
    return PermissionGrantModel.fromJson(json);
  }

  @override
  Future<List<SiteEntity>> siteScopes(int userId) async {
    final list = await _remote.siteScopes(userId);
    return list.map((e) => SiteModel.fromJson((e as Map<String, dynamic>)['site'] as Map<String, dynamic>)).toList();
  }

  @override
  Future<List<SiteEntity>> replaceSiteScopes(int userId, List<int> siteIds) async {
    final list = await _remote.replaceSiteScopes(userId, siteIds);
    return list.map((e) => SiteModel.fromJson((e as Map<String, dynamic>)['site'] as Map<String, dynamic>)).toList();
  }
}
