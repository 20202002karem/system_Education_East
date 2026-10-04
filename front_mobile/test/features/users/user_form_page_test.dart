import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:moehe_eastgaza_m1/core/network/api_exception.dart';
import 'package:moehe_eastgaza_m1/features/users/domain/entities/user.dart';
import 'package:moehe_eastgaza_m1/features/users/domain/repositories/users_repository.dart';
import 'package:moehe_eastgaza_m1/features/users/presentation/pages/user_form_page.dart';

class MockUsersRepository extends Mock implements UsersRepository {}

/// Validation test (instructions §17): a 422 from POST /users with
/// site_scope_ids required (Batch 3 §3.2, view_scope=sites) must render
/// inline under the right field — the same case covered on Web in
/// users.test.tsx.
void main() {
  late MockUsersRepository repository;

  setUp(() => repository = MockUsersRepository());

  Widget wrap() => MaterialApp(
        home: RepositoryProvider<UsersRepository>.value(
          value: repository,
          child: const UserFormPage(mode: UserFormMode.create),
        ),
      );

  testWidgets('shows the site_scope_ids validation message inline when view_scope=sites is missing scopes', (tester) async {
    when(() => repository.create(
          name: any(named: 'name'),
          loginIdentifier: any(named: 'loginIdentifier'),
          role: any(named: 'role'),
          viewScope: any(named: 'viewScope'),
          specialization: any(named: 'specialization'),
          siteScopeIds: any(named: 'siteScopeIds'),
        )).thenThrow(const ApiException(
      statusCode: 422,
      code: 'validation_failed',
      message: 'بيانات غير صالحة',
      fields: {
        'site_scope_ids': ['مطلوب عند view_scope=sites'],
      },
    ));

    await tester.pumpWidget(wrap());

    await tester.enterText(find.widgetWithText(TextField, 'الاسم'), 'موظف جديد');
    await tester.enterText(find.widgetWithText(TextField, 'البريد الإلكتروني'), 'new@moehe.example');

    await tester.tap(find.widgetWithText(DropdownButtonFormField<ViewScope>, 'نطاق العرض'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('مواقع محددة').last);
    await tester.pumpAndSettle();

    await tester.tap(find.widgetWithText(ElevatedButton, 'حفظ'));
    await tester.pumpAndSettle();

    expect(find.text('مطلوب عند view_scope=sites'), findsOneWidget);
  });

  testWidgets('pops with true on successful creation', (tester) async {
    when(() => repository.create(
          name: any(named: 'name'),
          loginIdentifier: any(named: 'loginIdentifier'),
          role: any(named: 'role'),
          viewScope: any(named: 'viewScope'),
          specialization: any(named: 'specialization'),
          siteScopeIds: any(named: 'siteScopeIds'),
        )).thenAnswer((_) async => const UserEntity(
          id: 9, name: 'موظف جديد', email: 'new@moehe.example',
          role: UserRole.technician, viewScope: ViewScope.assignedOnly, status: UserStatus.active, mfaEnabled: false,
        ));

    await tester.pumpWidget(MaterialApp(
      home: RepositoryProvider<UsersRepository>.value(
        value: repository,
        child: Builder(builder: (context) {
          return ElevatedButton(
            onPressed: () async {
              final result = await Navigator.of(context).push<bool>(
                MaterialPageRoute(builder: (_) => const UserFormPage(mode: UserFormMode.create)),
              );
              expect(result, isTrue);
            },
            child: const Text('open'),
          );
        }),
      ),
    ));

    await tester.tap(find.text('open'));
    await tester.pumpAndSettle();

    await tester.enterText(find.widgetWithText(TextField, 'الاسم'), 'موظف جديد');
    await tester.enterText(find.widgetWithText(TextField, 'البريد الإلكتروني'), 'new@moehe.example');
    await tester.tap(find.widgetWithText(ElevatedButton, 'حفظ'));
    await tester.pumpAndSettle();

    expect(find.text('إنشاء حساب'), findsNothing); // form page popped
  });
}
