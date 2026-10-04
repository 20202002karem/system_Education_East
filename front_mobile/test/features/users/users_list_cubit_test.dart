import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:moehe_eastgaza_m1/core/network/api_exception.dart';
import 'package:moehe_eastgaza_m1/core/utils/paginated.dart';
import 'package:moehe_eastgaza_m1/features/users/domain/entities/user.dart';
import 'package:moehe_eastgaza_m1/features/users/domain/repositories/users_repository.dart';
import 'package:moehe_eastgaza_m1/features/users/presentation/cubit/users_list_cubit.dart';
import 'package:moehe_eastgaza_m1/features/users/presentation/cubit/users_list_state.dart';

class MockUsersRepository extends Mock implements UsersRepository {}

void main() {
  late MockUsersRepository repository;

  const user = UserEntity(
    id: 1,
    name: 'خالد',
    email: 'khaled@moehe.example',
    role: UserRole.technician,
    viewScope: ViewScope.assignedOnly,
    status: UserStatus.active,
    mfaEnabled: false,
  );

  setUp(() => repository = MockUsersRepository());

  blocTest<UsersListCubit, UsersListState>(
    'emits [Loading, Success] with the paginated result on load()',
    build: () {
      when(() => repository.list(role: any(named: 'role'), status: any(named: 'status'), page: any(named: 'page')))
          .thenAnswer((_) async => const Paginated(items: [user], page: 1, perPage: 20, total: 1));
      return UsersListCubit(repository);
    },
    act: (cubit) => cubit.load(),
    expect: () => [
      const UsersListLoading(),
      const UsersListSuccess(Paginated(items: [user], page: 1, perPage: 20, total: 1)),
    ],
  );

  blocTest<UsersListCubit, UsersListState>(
    'emits [Loading, Failure] with the backend Arabic message on a 403 (non-chairman, Batch 3 §4)',
    build: () {
      when(() => repository.list(role: any(named: 'role'), status: any(named: 'status'), page: any(named: 'page')))
          .thenThrow(const ApiException(statusCode: 403, code: 'forbidden', message: 'لا صلاحية لتنفيذ هذا الإجراء'));
      return UsersListCubit(repository);
    },
    act: (cubit) => cubit.load(),
    expect: () => [const UsersListLoading(), const UsersListFailure('لا صلاحية لتنفيذ هذا الإجراء')],
  );

  blocTest<UsersListCubit, UsersListState>(
    'setRoleFilter re-loads from page 1 with the new filter applied',
    build: () {
      when(() => repository.list(role: 'chairman', status: any(named: 'status'), page: 1))
          .thenAnswer((_) async => const Paginated(items: [], page: 1, perPage: 20, total: 0));
      return UsersListCubit(repository);
    },
    act: (cubit) => cubit.setRoleFilter('chairman'),
    expect: () => [const UsersListLoading(), const UsersListSuccess(Paginated(items: [], page: 1, perPage: 20, total: 0))],
    verify: (_) {
      verify(() => repository.list(role: 'chairman', status: any(named: 'status'), page: 1)).called(1);
    },
  );
}
