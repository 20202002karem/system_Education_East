import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:moehe_eastgaza_m1/core/network/api_exception.dart';
import 'package:moehe_eastgaza_m1/core/utils/paginated.dart';
import 'package:moehe_eastgaza_m1/features/requests/domain/entities/service_request.dart';
import 'package:moehe_eastgaza_m1/features/requests/domain/repositories/requests_repository.dart';
import 'package:moehe_eastgaza_m1/features/requests/presentation/cubit/request_detail_cubit.dart';
import 'package:moehe_eastgaza_m1/features/requests/presentation/cubit/request_form_cubit.dart';
import 'package:moehe_eastgaza_m1/features/requests/presentation/cubit/requests_list_cubit.dart';

class MockRepo extends Mock implements RequestsRepository {}

ServiceRequestEntity _req({String status = 'assigned', int version = 3}) => ServiceRequestEntity.fromJson({
      'id': 7,
      'ref_no': 'REQ-2026-000007',
      'origin_site_id': 1,
      'description': 'عطل',
      'status': status,
      'cycle': {'id': 9, 'cycle_no': 1, 'status': status, 'assignee_user_id': 4, 'version': version},
    });

void main() {
  late MockRepo repo;
  setUp(() => repo = MockRepo());

  test('entity parses cycle, status label and maps held', () {
    final r = ServiceRequestEntity.fromJson({
      'id': 1, 'ref_no': 'REQ-1', 'origin_site_id': 2, 'description': 'x', 'status': 'held',
      'cycle': {'id': 1, 'cycle_no': 2, 'status': 'held', 'version': 5},
    });
    expect(r.statusLabel, 'معلّق');
    expect(r.cycle?.version, 5);
    expect(requestStatusLabels.keys, containsAll(['new', 'triaged', 'assigned', 'in_progress', 'held', 'reopened_pending_assignment', 'closed', 'cancelled']));
  });

  blocTest<RequestsListCubit, RequestsListState>(
    'list emits loading then success',
    build: () {
      when(() => repo.list(page: any(named: 'page'), q: any(named: 'q'), status: any(named: 'status')))
          .thenAnswer((_) async => Paginated(items: [_req()], page: 1, perPage: 20, total: 1));
      return RequestsListCubit(repo);
    },
    act: (c) => c.load(),
    expect: () => [isA<RequestsListLoading>(), isA<RequestsListSuccess>()],
  );

  blocTest<RequestsListCubit, RequestsListState>(
    'list failure carries the API message',
    build: () {
      when(() => repo.list(page: any(named: 'page'), q: any(named: 'q'), status: any(named: 'status')))
          .thenThrow(const ApiException(statusCode: 403, code: 'forbidden', message: 'لا صلاحية'));
      return RequestsListCubit(repo);
    },
    act: (c) => c.load(),
    expect: () => [isA<RequestsListLoading>(), isA<RequestsListFailure>()],
  );

  blocTest<RequestDetailCubit, RequestDetailState>(
    'start sends the cycle version and reloads',
    build: () {
      when(() => repo.get(7)).thenAnswer((_) async => _req());
      when(() => repo.notes(7, page: any(named: 'page'))).thenAnswer((_) async => const Paginated<RequestNoteEntity>(items: [], page: 1, perPage: 20, total: 0));
      when(() => repo.start(7, version: 3)).thenAnswer((_) async => _req(status: 'in_progress'));
      return RequestDetailCubit(repo, 7);
    },
    act: (c) async {
      await c.load();
      await c.start();
    },
    verify: (_) => verify(() => repo.start(7, version: 3)).called(1),
  );

  blocTest<RequestDetailCubit, RequestDetailState>(
    '409 reloads fresh state and surfaces the error',
    build: () {
      when(() => repo.get(7)).thenAnswer((_) async => _req());
      when(() => repo.notes(7, page: any(named: 'page'))).thenAnswer((_) async => const Paginated<RequestNoteEntity>(items: [], page: 1, perPage: 20, total: 0));
      when(() => repo.start(7, version: any(named: 'version')))
          .thenThrow(const ApiException(statusCode: 409, code: 'version_conflict', message: 'تعارض'));
      return RequestDetailCubit(repo, 7);
    },
    act: (c) async {
      await c.load();
      await c.start();
    },
    verify: (c) {
      final s = c.state as RequestDetailLoaded;
      expect(s.error, 'تعارض');
      expect(s.busy, false);
    },
  );

  blocTest<RequestFormCubit, RequestFormState>(
    'submit without required fields never calls the API',
    build: () => RequestFormCubit(repo),
    act: (c) => c.submit(siteId: null, channelId: 1, description: ''),
    verify: (c) {
      expect(c.state.error, isNotNull);
      verifyNever(() => repo.create(
            originSiteId: any(named: 'originSiteId'),
            channelId: any(named: 'channelId'),
            description: any(named: 'description'),
            suggestedPriority: any(named: 'suggestedPriority'),
            requesterName: any(named: 'requesterName'),
          ));
    },
  );

  blocTest<RequestFormCubit, RequestFormState>(
    'submit success exposes the created request',
    build: () {
      when(() => repo.create(
            originSiteId: any(named: 'originSiteId'),
            channelId: any(named: 'channelId'),
            description: any(named: 'description'),
            suggestedPriority: any(named: 'suggestedPriority'),
            requesterName: any(named: 'requesterName'),
          )).thenAnswer((_) async => _req(status: 'new'));
      return RequestFormCubit(repo);
    },
    act: (c) => c.submit(siteId: 1, channelId: 2, description: 'عطل'),
    verify: (c) => expect(c.state.created?.refNo, 'REQ-2026-000007'),
  );
}
