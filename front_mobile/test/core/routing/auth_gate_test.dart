import 'package:bloc_test/bloc_test.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:moehe_eastgaza_m1/core/routing/app_router.dart';
import 'package:moehe_eastgaza_m1/core/utils/paginated.dart';
import 'package:moehe_eastgaza_m1/features/assets/domain/repositories/assets_repository.dart';
import 'package:moehe_eastgaza_m1/features/auth/presentation/cubit/auth_cubit.dart';
import 'package:moehe_eastgaza_m1/features/auth/presentation/cubit/auth_state.dart';
import 'package:moehe_eastgaza_m1/features/users/domain/entities/user.dart';

class MockAuthCubit extends MockCubit<AuthState> implements AuthCubit {}
class MockAssetsRepository extends Mock implements AssetsRepository {}

/// Authorization UI test (instructions §17): the Flutter side must hide
/// chairman-only sections from non-chairman roles, exactly like the web
/// app's ProtectedRoute. This is the UX layer only — the backend
/// AuthorizationTest (Laravel suite) is the actual security boundary
/// (instructions §7); this docstring deliberately repeats that so the
/// assertions below are never mistaken for a security proof.
void main() {
  const chairman = UserEntity(
    id: 1, name: 'رئيس القسم', email: 'c@moehe.example',
    role: UserRole.chairman, viewScope: ViewScope.all, status: UserStatus.active, mfaEnabled: true,
  );
  const technician = UserEntity(
    id: 5, name: 'فني', email: 't@moehe.example',
    role: UserRole.technician, viewScope: ViewScope.assignedOnly, status: UserStatus.active, mfaEnabled: false,
  );

  late MockAuthCubit cubit;
  late MockAssetsRepository assets;

  setUp(() {
    cubit = MockAuthCubit();
    assets = MockAssetsRepository();
    when(() => assets.list(page: any(named: 'page'), perPage: any(named: 'perPage'), q: any(named: 'q'), status: any(named: 'status'), categoryId: any(named: 'categoryId'), siteId: any(named: 'siteId'), sort: any(named: 'sort')))
        .thenAnswer((_) async => const Paginated(items: [], page: 1, perPage: 20, total: 0));
  });

  Widget wrap() => RepositoryProvider<AssetsRepository>.value(
        value: assets,
        child: MaterialApp(home: BlocProvider<AuthCubit>.value(value: cubit, child: const AuthGate())),
      );

  testWidgets('shows the M1 landing (Users/Sites/Settings/Audit tiles) for a chairman', (tester) async {
    when(() => cubit.state).thenReturn(const AuthAuthenticated(chairman));
    whenListen(cubit, Stream<AuthState>.fromIterable([const AuthAuthenticated(chairman)]), initialState: const AuthAuthenticated(chairman));

    await tester.pumpWidget(wrap());
    await tester.pump();

    expect(find.text('المستخدمون'), findsOneWidget);
    expect(find.text('سجل التدقيق'), findsOneWidget);
  });

  testWidgets('hides all M1 admin sections for a non-chairman role and lands on the M2 assets list', (tester) async {
    when(() => cubit.state).thenReturn(const AuthAuthenticated(technician));
    whenListen(cubit, Stream<AuthState>.fromIterable([const AuthAuthenticated(technician)]), initialState: const AuthAuthenticated(technician));

    await tester.pumpWidget(wrap());
    await tester.pump();

    expect(find.text('المستخدمون'), findsNothing);
    await tester.pump();
    expect(find.text('الأجهزة'), findsOneWidget);
  });

  testWidgets('shows the login page when unauthenticated', (tester) async {
    when(() => cubit.state).thenReturn(const AuthUnauthenticated());
    whenListen(cubit, Stream<AuthState>.fromIterable([const AuthUnauthenticated()]), initialState: const AuthUnauthenticated());

    await tester.pumpWidget(wrap());
    await tester.pump();

    expect(find.text('تسجيل الدخول'), findsOneWidget);
  });
}
