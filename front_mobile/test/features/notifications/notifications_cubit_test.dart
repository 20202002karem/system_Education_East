import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:moehe_eastgaza_m1/core/utils/paginated.dart';
import 'package:moehe_eastgaza_m1/features/notifications/domain/entities/app_notification.dart';
import 'package:moehe_eastgaza_m1/features/notifications/domain/repositories/notifications_repository.dart';
import 'package:moehe_eastgaza_m1/features/notifications/presentation/cubit/notifications_cubit.dart';

class MockRepo extends Mock implements NotificationsRepository {}

void main() {
  late MockRepo repo;
  setUp(() => repo = MockRepo());

  const unread = AppNotificationEntity(id: 1, eventType: 'request.assigned', message: 'أُسند إليك طلب');

  test('isRead reflects read_at', () {
    expect(unread.isRead, false);
    expect(AppNotificationEntity.fromJson({'id': 2, 'event_type': 'x', 'message': 'm', 'read_at': '2026-10-09T08:00:00Z'}).isRead, true);
  });

  blocTest<NotificationsCubit, NotificationsState>(
    'markRead calls the API once and reloads; already-read is a no-op',
    build: () {
      when(() => repo.list(page: any(named: 'page'), read: any(named: 'read')))
          .thenAnswer((_) async => const Paginated(items: [unread], page: 1, perPage: 20, total: 1));
      when(() => repo.markRead(1)).thenAnswer((_) async => unread);
      return NotificationsCubit(repo);
    },
    act: (c) async {
      await c.load();
      await c.markRead(unread);
      await c.markRead(AppNotificationEntity.fromJson({'id': 2, 'event_type': 'x', 'message': 'm', 'read_at': '2026-10-09T08:00:00Z'}));
    },
    verify: (_) {
      verify(() => repo.markRead(1)).called(1);
      verifyNever(() => repo.markRead(2));
    },
  );
}
