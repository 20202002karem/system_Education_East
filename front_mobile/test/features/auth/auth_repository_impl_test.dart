import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

import 'package:moehe_eastgaza_m1/core/storage/secure_token_storage.dart';
import 'package:moehe_eastgaza_m1/features/auth/data/datasources/auth_remote_datasource.dart';
import 'package:moehe_eastgaza_m1/features/auth/data/repositories/auth_repository_impl.dart';

class MockAuthRemoteDataSource extends Mock implements AuthRemoteDataSource {}

class MockSecureTokenStorage extends Mock implements SecureTokenStorage {}

void main() {
  late MockAuthRemoteDataSource remote;
  late MockSecureTokenStorage tokenStorage;
  late AuthRepositoryImpl repository;

  setUp(() {
    remote = MockAuthRemoteDataSource();
    tokenStorage = MockSecureTokenStorage();

    repository = AuthRepositoryImpl(
      remote,
      tokenStorage,
    );
  });

  group('AuthRepositoryImpl.login', () {
    test(
      'stores the access token after a successful login',
      () async {
        when(
          () => remote.login(any(), any()),
        ).thenAnswer(
          (_) async => {
            'access_token': 'tok-xyz',
            'token_type': 'Bearer',
            'expires_in': 1800,
          },
        );

        when(
          () => tokenStorage.save(any()),
        ).thenAnswer((_) async {});

        await repository.login(
          loginIdentifier: 'chairman@moehe.example',
          password: 'Password-123',
        );

        verify(
          () => tokenStorage.save('tok-xyz'),
        ).called(1);
      },
    );

    test(
      'works for any active user including chairman',
      () async {
        when(
          () => remote.login(any(), any()),
        ).thenAnswer(
          (_) async => {
            'access_token': 'chairman-token',
            'token_type': 'Bearer',
            'expires_in': 1800,
          },
        );

        when(
          () => tokenStorage.save(any()),
        ).thenAnswer((_) async {});

        await repository.login(
          loginIdentifier: 'chairman@moehe.example',
          password: 'Password-123',
        );

        verify(
          () => tokenStorage.save('chairman-token'),
        ).called(1);
      },
    );

    test(
      'does not save a token when login throws',
      () async {
        when(
          () => remote.login(any(), any()),
        ).thenThrow(
          Exception('invalid credentials'),
        );

        await expectLater(
          repository.login(
            loginIdentifier: 'wrong@example.com',
            password: 'wrong',
          ),
          throwsException,
        );

        verifyNever(
          () => tokenStorage.save(any()),
        );
      },
    );
  });

  group('AuthRepositoryImpl.logout', () {
    test(
      'clears the local token even if the remote call throws',
      () async {
        when(
          () => remote.logout(),
        ).thenThrow(
          Exception('network down'),
        );

        when(
          () => tokenStorage.clear(),
        ).thenAnswer((_) async {});

        await expectLater(
          repository.logout(),
          throwsException,
        );

        verify(
          () => tokenStorage.clear(),
        ).called(1);
      },
    );
  });

  group('AuthRepositoryImpl.hasStoredSession', () {
    test(
      'returns false when no token is stored',
      () async {
        when(
          () => tokenStorage.read(),
        ).thenAnswer((_) async => null);

        expect(
          await repository.hasStoredSession(),
          isFalse,
        );
      },
    );

    test(
      'returns true when a token is stored',
      () async {
        when(
          () => tokenStorage.read(),
        ).thenAnswer((_) async => 'tok-xyz');

        expect(
          await repository.hasStoredSession(),
          isTrue,
        );
      },
    );
  });
}
