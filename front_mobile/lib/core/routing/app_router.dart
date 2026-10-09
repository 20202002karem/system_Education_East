import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../features/auth/presentation/cubit/auth_cubit.dart';
import '../../features/auth/presentation/cubit/auth_state.dart';
import '../../features/auth/presentation/pages/login_page.dart';
import '../../features/assets/domain/repositories/assets_repository.dart';
import '../../features/assets/presentation/cubit/assets_list_cubit.dart';
import '../../features/assets/presentation/pages/assets_list_page.dart';
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
import '../../features/requests/domain/repositories/requests_repository.dart';
import '../../features/requests/presentation/cubit/requests_list_cubit.dart';
import '../../features/requests/presentation/pages/requests_list_page.dart';
import '../../features/tasks/domain/repositories/tasks_repository.dart';
import '../../features/tasks/presentation/cubit/tasks_list_cubit.dart';
import '../../features/tasks/presentation/pages/tasks_list_page.dart';
import '../../features/notifications/domain/repositories/notifications_repository.dart';
import '../../features/notifications/presentation/cubit/notifications_cubit.dart';
import '../../features/notifications/presentation/pages/notifications_page.dart';
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
          AuthAuthenticated(:final user) => _AssetsHome(role: user.role),
          _ => const LoginPage(),
        };
      },
    );
  }
}

/// Non-chairman roles have no M1 admin screens; their entry point is the M2
/// assets list (read scope is enforced by the server per view_scope).
class _AssetsHome extends StatelessWidget {
  const _AssetsHome({required this.role});
  final UserRole role;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (ctx) => AssetsListCubit(ctx.read<AssetsRepository>()),
      child: AssetsListPage(role: role, onLogout: () => context.read<AuthCubit>().logout()),
    );
  }
}

Route<dynamic> onGenerateRoute(RouteSettings settings) {
  switch (settings.name) {
    case '/assets':
      return MaterialPageRoute(
        builder: (ctx) => BlocProvider(
          create: (c) => AssetsListCubit(c.read<AssetsRepository>()),
          child: AssetsListPage(role: (ctx.read<AuthCubit>().state as AuthAuthenticated).user.role),
        ),
      );
    case '/requests':
      return MaterialPageRoute(builder: (c) => BlocProvider(create: (_) => RequestsListCubit(c.read<RequestsRepository>()), child: const RequestsListPage()));
    case '/tasks':
      return MaterialPageRoute(builder: (c) => BlocProvider(create: (_) => TasksListCubit(c.read<TasksRepository>()), child: const TasksListPage()));
    case '/notifications':
      return MaterialPageRoute(builder: (c) => BlocProvider(create: (_) => NotificationsCubit(c.read<NotificationsRepository>()), child: const NotificationsPage()));
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
