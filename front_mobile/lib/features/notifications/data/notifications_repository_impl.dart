import '../../../core/utils/paginated.dart';
import '../domain/entities/app_notification.dart';
import '../domain/repositories/notifications_repository.dart';
import 'notifications_remote_datasource.dart';

class NotificationsRepositoryImpl implements NotificationsRepository {
  NotificationsRepositoryImpl(this._ds);
  final NotificationsRemoteDataSource _ds;

  @override
  Future<Paginated<AppNotificationEntity>> list({int page = 1, bool? read}) async {
    final r = await _ds.list({'page': page, if (read != null) 'read': read ? 'true' : 'false'});
    return Paginated<AppNotificationEntity>(
      items: r.data.map((e) => AppNotificationEntity.fromJson(e as Map<String, dynamic>)).toList(),
      page: (r.meta['page'] as num?)?.toInt() ?? 1,
      perPage: (r.meta['per_page'] as num?)?.toInt() ?? 20,
      total: (r.meta['total'] as num?)?.toInt() ?? r.data.length,
    );
  }

  @override
  Future<AppNotificationEntity> markRead(int id) async => AppNotificationEntity.fromJson(await _ds.markRead(id));
}
