import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../domain/entities/permission_grant.dart';
import '../../domain/repositories/users_repository.dart';
import 'user_detail_state.dart';

/// Backs the User Details screen: profile, sessions, reset-password,
/// permission grants (Batch 3 §2.2 sub-resources of a single user).
class UserDetailCubit extends Cubit<UserDetailState> {
  UserDetailCubit(this._repository, this.userId) : super(const UserDetailInitial());
  final UsersRepository _repository;
  final int userId;

  Future<void> load() async {
    emit(const UserDetailLoading());
    try {
      final user = await _repository.get(userId);
      final grants = await _repository.permissionGrants(userId);
      emit(UserDetailSuccess(user, grants));
    } on ApiException catch (e) {
      emit(UserDetailFailure(e.message));
    }
  }

  Future<void> disable() => _runAction((s) async {
        final user = await _repository.disable(userId);
        return s.copyWith(user: user);
      });

  Future<void> enable() => _runAction((s) async {
        final user = await _repository.enable(userId);
        return s.copyWith(user: user);
      });

  Future<void> terminateSessions() => _runAction((s) async {
        await _repository.terminateSessions(userId);
        return s;
      });

  Future<void> resetPassword(String password) => _runAction((s) async {
        await _repository.resetPassword(userId, password);
        return s;
      });

  Future<void> grantPermission(PermissionKey key, {String? reason}) => _runAction((s) async {
        await _repository.grantPermission(userId, key: key, reason: reason);
        final grants = await _repository.permissionGrants(userId);
        return s.copyWith(grants: grants);
      });

  Future<void> revokePermission(int grantId) => _runAction((s) async {
        await _repository.revokePermission(grantId);
        final grants = await _repository.permissionGrants(userId);
        return s.copyWith(grants: grants);
      });

  Future<void> _runAction(Future<UserDetailSuccess> Function(UserDetailSuccess current) action) async {
    final current = state;
    if (current is! UserDetailSuccess) return;
    emit(current.copyWith(actionInFlight: true));
    try {
      final next = await action(current);
      emit(next.copyWith(actionInFlight: false));
    } on ApiException catch (e) {
      emit(current.copyWith(actionInFlight: false, actionError: e.message));
    }
  }
}
