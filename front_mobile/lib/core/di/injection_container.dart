import 'package:get_it/get_it.dart';
import '../network/api_client.dart';
import '../storage/secure_token_storage.dart';
import '../../features/auth/data/datasources/auth_remote_datasource.dart';
import '../../features/auth/data/repositories/auth_repository_impl.dart';
import '../../features/auth/domain/repositories/auth_repository.dart';
import '../../features/users/data/datasources/users_remote_datasource.dart';
import '../../features/users/data/repositories/users_repository_impl.dart';
import '../../features/users/domain/repositories/users_repository.dart';
import '../../features/organization/data/datasources/organization_remote_datasource.dart';
import '../../features/organization/data/repositories/organization_repository_impl.dart';
import '../../features/organization/domain/repositories/organization_repository.dart';
import '../../features/settings/data/datasources/settings_remote_datasource.dart';
import '../../features/settings/data/repositories/settings_repository_impl.dart';
import '../../features/settings/domain/repositories/settings_repository.dart';
import '../../features/assets/data/datasources/assets_remote_datasource.dart';
import '../../features/assets/data/repositories/assets_repository_impl.dart';
import '../../features/assets/domain/repositories/assets_repository.dart';
import '../../features/audit/data/datasources/audit_remote_datasource.dart';
import '../../features/audit/data/repositories/audit_repository_impl.dart';
import '../../features/audit/domain/repositories/audit_repository.dart';
import '../../features/requests/data/requests_remote_datasource.dart';
import '../../features/requests/data/requests_repository_impl.dart';
import '../../features/requests/domain/repositories/requests_repository.dart';
import '../../features/tasks/data/tasks_remote_datasource.dart';
import '../../features/tasks/data/tasks_repository_impl.dart';
import '../../features/tasks/domain/repositories/tasks_repository.dart';
import '../../features/notifications/data/notifications_remote_datasource.dart';
import '../../features/notifications/data/notifications_repository_impl.dart';
import '../../features/notifications/domain/repositories/notifications_repository.dart';

final getIt = GetIt.instance;

/// Simple service locator (instructions §5: keep the data/API layer
/// separate from presentation). Called once from main() before runApp().
/// Every repository is registered against its DOMAIN interface, so
/// presentation (Cubits) never imports a *_impl.dart or *RemoteDataSource
/// directly — only the abstract repository.
void setupDependencies() {
  getIt.registerLazySingleton<SecureTokenStorage>(() => SecureTokenStorage());
  getIt.registerLazySingleton<ApiClient>(() => ApiClient(tokenStorage: getIt()));

  getIt.registerLazySingleton<AuthRemoteDataSource>(() => AuthRemoteDataSource(getIt()));
  getIt.registerLazySingleton<AuthRepository>(() => AuthRepositoryImpl(getIt(), getIt()));

  getIt.registerLazySingleton<UsersRemoteDataSource>(() => UsersRemoteDataSource(getIt()));
  getIt.registerLazySingleton<UsersRepository>(() => UsersRepositoryImpl(getIt()));

  getIt.registerLazySingleton<OrganizationRemoteDataSource>(() => OrganizationRemoteDataSource(getIt()));
  getIt.registerLazySingleton<OrganizationRepository>(() => OrganizationRepositoryImpl(getIt()));

  getIt.registerLazySingleton<SettingsRemoteDataSource>(() => SettingsRemoteDataSource(getIt()));
  getIt.registerLazySingleton<SettingsRepository>(() => SettingsRepositoryImpl(getIt()));

  getIt.registerLazySingleton<AuditRemoteDataSource>(() => AuditRemoteDataSource(getIt()));
  getIt.registerLazySingleton<AuditRepository>(() => AuditRepositoryImpl(getIt()));

  getIt.registerLazySingleton<AssetsRemoteDataSource>(() => AssetsRemoteDataSource(getIt()));
  getIt.registerLazySingleton<AssetsRepository>(() => AssetsRepositoryImpl(getIt()));

  // M3 — requests, tasks, internal notifications.
  getIt.registerLazySingleton<RequestsRemoteDataSource>(() => RequestsRemoteDataSource(getIt()));
  getIt.registerLazySingleton<RequestsRepository>(() => RequestsRepositoryImpl(getIt()));
  getIt.registerLazySingleton<TasksRemoteDataSource>(() => TasksRemoteDataSource(getIt()));
  getIt.registerLazySingleton<TasksRepository>(() => TasksRepositoryImpl(getIt()));
  getIt.registerLazySingleton<NotificationsRemoteDataSource>(() => NotificationsRemoteDataSource(getIt()));
  getIt.registerLazySingleton<NotificationsRepository>(() => NotificationsRepositoryImpl(getIt()));
}
