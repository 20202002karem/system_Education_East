import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'core/di/injection_container.dart';
import 'core/network/api_client.dart';
import 'core/routing/app_router.dart';
import 'core/theme/app_theme.dart';
import 'features/assets/domain/repositories/assets_repository.dart';
import 'features/audit/domain/repositories/audit_repository.dart';
import 'features/auth/domain/repositories/auth_repository.dart';
import 'features/auth/presentation/cubit/auth_cubit.dart';
import 'features/organization/domain/repositories/organization_repository.dart';
import 'features/settings/domain/repositories/settings_repository.dart';
import 'features/users/domain/repositories/users_repository.dart';

void main() {
  setupDependencies();
  runApp(const M1App());
}

class M1App extends StatefulWidget {
  const M1App({super.key});

  @override
  State<M1App> createState() => _M1AppState();
}

class _M1AppState extends State<M1App> {
  late final AuthCubit _authCubit;

  @override
  void initState() {
    super.initState();
    _authCubit = AuthCubit(getIt<AuthRepository>());
    // Any 401 anywhere in the app (an expired/invalid token on a Users/Sites/
    // Settings/Audit call, not just from the auth endpoints themselves) routes
    // back through AuthCubit so the UI falls back to the login screen
    // (instructions §6: "التعامل مع انتهاء الجلسة").
    getIt<ApiClient>().onUnauthorized = _authCubit.forceUnauthenticated;
    _authCubit.bootstrap();
  }

  @override
  void dispose() {
    _authCubit.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MultiRepositoryProvider(
      providers: [
        RepositoryProvider<UsersRepository>.value(value: getIt<UsersRepository>()),
        RepositoryProvider<OrganizationRepository>.value(value: getIt<OrganizationRepository>()),
        RepositoryProvider<SettingsRepository>.value(value: getIt<SettingsRepository>()),
        RepositoryProvider<AuditRepository>.value(value: getIt<AuditRepository>()),
        RepositoryProvider<AssetsRepository>.value(value: getIt<AssetsRepository>()),
      ],
      child: BlocProvider.value(
        value: _authCubit,
        child: MaterialApp(
          title: 'نظام قسم الحاسوب — شرق غزة',
          debugShowCheckedModeBanner: false,
          theme: AppTheme.light(),
          locale: const Locale('ar'),
          supportedLocales: const [Locale('ar')], // English can be added later (instructions §5) without restructuring — see core/l10n/app_strings.dart
          localizationsDelegates: const [
            GlobalMaterialLocalizations.delegate,
            GlobalWidgetsLocalizations.delegate,
            GlobalCupertinoLocalizations.delegate,
          ],
          builder: (context, child) => Directionality(textDirection: TextDirection.rtl, child: child!),
          home: const AuthGate(),
          onGenerateRoute: onGenerateRoute,
        ),
      ),
    );
  }
}
