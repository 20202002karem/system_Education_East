import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../features/auth/presentation/cubit/auth_cubit.dart';
import '../../features/auth/presentation/cubit/auth_state.dart';
import '../../features/auth/presentation/pages/login_page.dart';
import '../../features/audit/presentation/pages/audit_log_page.dart';
import '../../features/organization/presentation/cubit/sites_list_cubit.dart';
import '../../features/organization/presentation/pages/sites_list_page.dart';
import '../../features/settings/presentation/cubit/reference_data_cubit.dart';
import '../../features/settings/presentation/cubit/settings_cubit.dart';
import '../../features/settings/presentation/pages/reference_data_page.dart';
import '../../features/settings/presentation/pages/settings_page.dart';
import '../../features/users/domain/entities/user.dart';
import '../../features/users/presentation/cubit/users_list_cubit.dart';
import '../../features/users/presentation/pages/users_list_page.dart';
import '../di/injection_container.dart';
import 'landing_page.dart';

/// Navigation is M1-only (instructions §15): Auth → Landing → Users /
/// Organization / Settings / Audit. No dashboard with M2 statistics exists
/// anywhere in this tree, by design.
///
/// AuthGate is the single place that decides what the root shows: it listens
/// to AuthCubit and swaps Login <-> Landing. Every M1 section route below is
/// wrapped in a role check because, per Batch 3 §4, every M1 admin area is
/// chairman-only — a non-chairman role never even reaches these pages
/// (UX layer only; the backend re-checks regardless, instructions §7).
class AuthGate extends StatelessWidget {
  const AuthGate({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AuthCubit, AuthState>(
      builder: (context, state) {
        return switch (state) {
          AuthAuthenticated(:final user) when user.role == UserRole.chairman => const LandingPage(),
          AuthAuthenticated() => const _NonChairmanNotice(),
          _ => const LoginPage(),
        };
      },
    );
  }
}

class _NonChairmanNotice extends StatelessWidget {
  const _NonChairmanNotice();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            const Text('لا توجد شاشات إدارية متاحة لدورك في M1 حاليًا.', textAlign: TextAlign.center),
            const SizedBox(height: 12),
            TextButton(onPressed: () => context.read<AuthCubit>().logout(), child: const Text('تسجيل الخروج')),
          ]),
        ),
      ),
    );
  }
}

Route<dynamic> onGenerateRoute(RouteSettings settings) {
  switch (settings.name) {
    case '/users':
      return MaterialPageRoute(builder: (_) => BlocProvider(create: (_) => UsersListCubit(getIt()), child: const UsersListPage()));
    case '/sites':
      return MaterialPageRoute(builder: (_) => BlocProvider(create: (_) => SitesListCubit(getIt()), child: const SitesListPage()));
    case '/settings':
      return MaterialPageRoute(builder: (_) => BlocProvider(create: (_) => SettingsCubit(getIt()), child: const SettingsPage()));
    case '/reference':
      return MaterialPageRoute(builder: (_) => BlocProvider(create: (_) => ReferenceDataCubit(getIt()), child: const ReferenceDataPage()));
    case '/audit':
      return MaterialPageRoute(builder: (_) => const AuditLogPage());
    default:
      return MaterialPageRoute(builder: (_) => const AuthGate());
  }
}
