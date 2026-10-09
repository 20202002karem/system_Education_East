import '../../../../core/utils/paginated.dart';
import '../entities/task.dart';

/// Mobile scope: assignee-side reading and work transitions only (M3-API-017/018/020..023).
abstract class TasksRepository {
  Future<Paginated<TaskEntity>> list({int page = 1, String? status});
  Future<TaskEntity> get(int id);
  Future<TaskEntity> start(int id, {int? version});
  Future<TaskEntity> hold(int id, {int? version});
  Future<TaskEntity> resume(int id, {int? version});
  Future<TaskEntity> complete(int id, {required String resultSummary, int? version});
}
