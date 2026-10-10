import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:moehe_eastgaza_m1/core/network/api_exception.dart';
import 'package:moehe_eastgaza_m1/core/utils/paginated.dart';
import 'package:moehe_eastgaza_m1/features/tasks/domain/entities/task.dart';
import 'package:moehe_eastgaza_m1/features/tasks/domain/repositories/tasks_repository.dart';
import 'package:moehe_eastgaza_m1/features/tasks/presentation/cubit/task_detail_cubit.dart';
import 'package:moehe_eastgaza_m1/features/tasks/presentation/cubit/tasks_list_cubit.dart';

class MockRepo extends Mock implements TasksRepository {}

TaskEntity _task({String status = 'assigned', int version = 2}) =>
    TaskEntity(id: 3, refNo: 'TSK-2026-000003', title: 'فحص', status: status, assigneeId: 4, version: version);

void main() {
  late MockRepo repo;
  setUp(() => repo = MockRepo());

  test('task status labels cover the six statuses', () {
    expect(taskStatusLabels.keys, containsAll(['new', 'assigned', 'in_progress', 'held', 'completed', 'cancelled']));
  });

  blocTest<TasksListCubit, TasksListState>(
    'list loads',
    build: () {
      when(() => repo.list(page: any(named: 'page'), status: any(named: 'status')))
          .thenAnswer((_) async => Paginated(items: [_task()], page: 1, perPage: 20, total: 1));
      return TasksListCubit(repo);
    },
    act: (c) => c.load(),
    expect: () => [isA<TasksListLoading>(), isA<TasksListSuccess>()],
  );

  blocTest<TaskDetailCubit, TaskDetailState>(
    'complete sends result summary with version',
    build: () {
      when(() => repo.get(3)).thenAnswer((_) async => _task(status: 'in_progress'));
      when(() => repo.complete(3, resultSummary: 'تم', version: 2)).thenAnswer((_) async => _task(status: 'completed', version: 3));
      return TaskDetailCubit(repo, 3);
    },
    act: (c) async {
      await c.load();
      await c.complete('تم');
    },
    verify: (c) {
      expect((c.state as TaskDetailLoaded).task.status, 'completed');
      verify(() => repo.complete(3, resultSummary: 'تم', version: 2)).called(1);
    },
  );

  blocTest<TaskDetailCubit, TaskDetailState>(
    'forbidden transition surfaces the server message',
    build: () {
      when(() => repo.get(3)).thenAnswer((_) async => _task());
      when(() => repo.start(3, version: any(named: 'version')))
          .thenThrow(const ApiException(statusCode: 403, code: 'forbidden', message: 'لا صلاحية'));
      return TaskDetailCubit(repo, 3);
    },
    act: (c) async {
      await c.load();
      await c.start();
    },
    verify: (c) => expect((c.state as TaskDetailLoaded).error, 'لا صلاحية'),
  );
}
