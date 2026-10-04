import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:moehe_eastgaza_m1/core/network/api_exception.dart';
import 'package:moehe_eastgaza_m1/features/auth/domain/repositories/auth_repository.dart';
import 'package:moehe_eastgaza_m1/features/auth/presentation/cubit/auth_cubit.dart';
import 'package:moehe_eastgaza_m1/features/auth/presentation/cubit/auth_state.dart';
import 'package:moehe_eastgaza_m1/features/users/domain/entities/user.dart';

class MockAuthRepository extends Mock implements AuthRepository {}

void main() {
  late MockAuthRepository repository;

  const technician = UserEntity(
    id: 5,
    name: 'فني',
    email: 'tech@moehe.example',
    role: UserRole.technician,
    viewScope: ViewScope.assignedOnly,
    status: UserStatus.active,
    mfaEnabled: false,
  );

  const chairman = UserEntity(
    id: 1,
    name: 'رئيس القسم',
    email: 'chairman@moehe.example',
    role: UserRole.chairman,
    viewScope: ViewScope.all,
    status: UserStatus.active,
    mfaEnabled: false,
  );

  setUp(() {
    repository = MockAuthRepository();
  });

  group('AuthCubit.login', () {
    blocTest<AuthCubit, AuthState>(
      'emits [Loading, Authenticated] for a valid login',
      build: () {
        when(
          () => repository.login(
            loginIdentifier: any(named: 'loginIdentifier'),
            password: any(named: 'password'),
          ),
        ).thenAnswer((_) async {});

        when(
          () => repository.getCurrentUser(),
        ).thenAnswer((_) async => technician);

        return AuthCubit(repository);
      },
      act: (cubit) => cubit.login(
        'tech@moehe.example',
        'Password-123',
      ),
      expect: () => [
        const AuthLoading(),
        const AuthAuthenticated(technician),
      ],
    );

    blocTest<AuthCubit, AuthState>(
      'emits [Loading, Authenticated] for a chairman without MFA',
      build: () {
        when(
          () => repository.login(
            loginIdentifier: any(named: 'loginIdentifier'),
            password: any(named: 'password'),
          ),
        ).thenAnswer((_) async {});

        when(
          () => repository.getCurrentUser(),
        ).thenAnswer((_) async => chairman);

        return AuthCubit(repository);
      },
      act: (cubit) => cubit.login(
        'chairman@moehe.example',
        'Password-123',
      ),
      expect: () => [
        const AuthLoading(),
        const AuthAuthenticated(chairman),
      ],
    );

    blocTest<AuthCubit, AuthState>(
      'emits [Loading, Unauthenticated] with the backend message on invalid credentials',
      build: () {
        when(
          () => repository.login(
            loginIdentifier: any(named: 'loginIdentifier'),
            password: any(named: 'password'),
          ),
        ).thenThrow(
          const ApiException(
            statusCode: 401,
            code: 'invalid_credentials',
            message: 'بيانات الدخول غير صحيحة',
          ),
        );

        return AuthCubit(repository);
      },
      act: (cubit) => cubit.login(
        'x@example.com',
        'wrong',
      ),
      expect: () => [
        const AuthLoading(),
        const AuthUnauthenticated(
          error: 'بيانات الدخول غير صحيحة',
        ),
      ],
    );

    blocTest<AuthCubit, AuthState>(
      'surfaces account_locked (423) distinctly from invalid_credentials',
      build: () {
        when(
          () => repository.login(
            loginIdentifier: any(named: 'loginIdentifier'),
            password: any(named: 'password'),
          ),
        ).thenThrow(
          const ApiException(
            statusCode: 423,
            code: 'account_locked',
            message: 'الحساب مقفل مؤقتاً، حاول لاحقاً',
          ),
        );

        return AuthCubit(repository);
      },
      act: (cubit) => cubit.login(
        'locked@example.com',
        'whatever',
      ),
      expect: () => [
        const AuthLoading(),
        const AuthUnauthenticated(
          error: 'الحساب مقفل مؤقتاً، حاول لاحقاً',
        ),
      ],
    );
  });

  group('AuthCubit.bootstrap', () {
    blocTest<AuthCubit, AuthState>(
      'restores a valid stored session via /auth/me',
      build: () {
        when(
          () => repository.hasStoredSession(),
        ).thenAnswer((_) async => true);

        when(
          () => repository.getCurrentUser(),
        ).thenAnswer((_) async => chairman);

        return AuthCubit(repository);
      },
      act: (cubit) => cubit.bootstrap(),
      expect: () => [
        const AuthLoading(),
        const AuthAuthenticated(chairman),
      ],
    );

    blocTest<AuthCubit, AuthState>(
      'falls back to Unauthenticated when the stored token is invalid',
      build: () {
        when(
          () => repository.hasStoredSession(),
        ).thenAnswer((_) async => true);

        when(
          () => repository.getCurrentUser(),
        ).thenThrow(
          const ApiException(
            statusCode: 401,
            code: 'unauthenticated',
            message: 'غير مُصادَق',
          ),
        );

        return AuthCubit(repository);
      },
      act: (cubit) => cubit.bootstrap(),
      expect: () => [
        const AuthLoading(),
        const AuthUnauthenticated(),
      ],
    );
  });

  group('AuthCubit.forceUnauthenticated', () {
    blocTest<AuthCubit, AuthState>(
      'forces logout from authenticated state',
      build: () => AuthCubit(repository),
      seed: () => const AuthAuthenticated(technician),
      act: (cubit) => cubit.forceUnauthenticated(),
      expect: () => [
        const AuthUnauthenticated(
          error: 'انتهت الجلسة، يرجى تسجيل الدخول مجددًا',
        ),
      ],
    );

    blocTest<AuthCubit, AuthState>(
      'is a no-op when not currently authenticated',
      build: () => AuthCubit(repository),
      seed: () => const AuthUnauthenticated(),
      act: (cubit) => cubit.forceUnauthenticated(),
      expect: () => <AuthState>[],
    );
  });
}
