import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:moehe_eastgaza_m1/core/network/api_exception.dart';
import 'package:moehe_eastgaza_m1/features/users/domain/entities/permission_grant.dart';
import 'package:moehe_eastgaza_m1/features/users/domain/entities/user.dart';
import 'package:moehe_eastgaza_m1/features/users/domain/repositories/users_repository.dart';
import 'package:moehe_eastgaza_m1/features/users/presentation/cubit/user_detail_cubit.dart';
import 'package:moehe_eastgaza_m1/features/users/presentation/cubit/user_detail_state.dart';

class MockUsersRepository extends Mock implements UsersRepository {}

void main() {
  late MockUsersRepository repository;

  const user = UserEntity(
    id: 5,
    name: 'فني',
    email: 'tech@moehe.example',
    role: UserRole.technician,
    viewScope: ViewScope.assignedOnly,
    status: UserStatus.active,
    mfaEnabled: false,
  );
  final disabledUser = UserEntity(
    id: user.id,
    name: user.name,
    email: user.email,
    role: user.role,
    viewScope: user.viewScope,
    status: UserStatus.disabled,
    mfaEnabled: user.mfaEnabled,
  );

  setUp(() => repository = MockUsersRepository());

  blocTest<UserDetailCubit, UserDetailState>(
    'load() emits [Loading, Success] with user + permission grants',
    build: () {
      when(() => repository.get(5)).thenAnswer((_) async => user);
      when(() => repository.permissionGrants(5)).thenAnswer((_) async => const []);
      return UserDetailCubit(repository, 5);
    },
    act: (cubit) => cubit.load(),
    expect: () => [const UserDetailLoading(), const UserDetailSuccess(user, [])],
  );

  blocTest<UserDetailCubit, UserDetailState>(
    'disable() updates the user status in-place without a full reload',
    build: () {
      when(() => repository.disable(5)).thenAnswer((_) async => disabledUser);
      return UserDetailCubit(repository, 5);
    },
    seed: () => const UserDetailSuccess(user, []),
    act: (cubit) => cubit.disable(),
    expect: () => [
      const UserDetailSuccess(user, [], actionInFlight: true),
      UserDetailSuccess(disabledUser, const []),
    ],
  );

  blocTest<UserDetailCubit, UserDetailState>(
    'grantPermission() reloads the grants list on success (AUTH-08)',
    build: () {
      final grant = PermissionGrantEntity(
        id: 1, userId: 5, permissionKey: PermissionKey.editAssets, grantedBy: 1, grantedAt: DateTime.utc(2026, 1, 1),
      );
      when(() => repository.grantPermission(5, key: PermissionKey.editAssets, reason: any(named: 'reason')))
          .thenAnswer((_) async => grant);
      when(() => repository.permissionGrants(5)).thenAnswer((_) async => [grant]);
      return UserDetailCubit(repository, 5);
    },
    seed: () => const UserDetailSuccess(user, []),
    act: (cubit) => cubit.grantPermission(PermissionKey.editAssets),
    verify: (_) {
      verify(() => repository.permissionGrants(5)).called(1);
    },
  );

  blocTest<UserDetailCubit, UserDetailState>(
    'surfaces action errors without discarding the current user/grants data',
    build: () {
      when(() => repository.disable(5)).thenThrow(const ApiException(statusCode: 500, code: 'server_error', message: 'حدث خطأ في الخادم، حاول لاحقاً'));
      return UserDetailCubit(repository, 5);
    },
    seed: () => const UserDetailSuccess(user, []),
    act: (cubit) => cubit.disable(),
    expect: () => [
      const UserDetailSuccess(user, [], actionInFlight: true),
      const UserDetailSuccess(user, [], actionError: 'حدث خطأ في الخادم، حاول لاحقاً'),
    ],
  );
}
