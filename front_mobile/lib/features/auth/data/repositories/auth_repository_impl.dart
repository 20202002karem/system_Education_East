import '../../../../core/storage/secure_token_storage.dart';
import '../../../users/data/models/user_model.dart';
import '../../../users/domain/entities/user.dart';
import '../../domain/repositories/auth_repository.dart';
import '../datasources/auth_remote_datasource.dart';

class AuthRepositoryImpl implements AuthRepository {
  AuthRepositoryImpl(this._remote, this._tokenStorage);

  final AuthRemoteDataSource _remote;
  final SecureTokenStorage _tokenStorage;

  @override
  Future<void> login(
      {required String loginIdentifier, required String password}) async {
    final data = await _remote.login(loginIdentifier, password);

    final token = data['access_token'] as String;
    await _tokenStorage.save(token);
  }

  @override
  Future<void> logout() async {
    try {
      await _remote.logout();
    } finally {
      // Always clear the local token even if the network call fails, so the
      // user is never stuck "logged in" on-device against their will.
      await _tokenStorage.clear();
    }
  }

  @override
  Future<UserEntity> getCurrentUser() async {
    final data = await _remote.me();
    return UserModel.fromJson(data);
  }

  @override
  Future<bool> hasStoredSession() async {
    final token = await _tokenStorage.read();
    return token != null && token.isNotEmpty;
  }
}
