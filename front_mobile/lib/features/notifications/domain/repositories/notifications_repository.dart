import '../../../../core/utils/paginated.dart';
import '../entities/app_notification.dart';

/// Internal notifications only (M3-API-035/036) — no email/SMS/push.
abstract class NotificationsRepository {
  Future<Paginated<AppNotificationEntity>> list({int page = 1, bool? read});
  Future<AppNotificationEntity> markRead(int id);
}
