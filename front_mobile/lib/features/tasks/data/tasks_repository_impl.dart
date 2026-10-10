import '../../../core/utils/paginated.dart';
import '../domain/entities/task.dart';
import '../domain/repositories/tasks_repository.dart';
import 'tasks_remote_datasource.dart';

class TasksRepositoryImpl implements TasksRepository {
  TasksRepositoryImpl(this._ds);
  final TasksRemoteDataSource _ds;

  Map<String, dynamic> _v(int? version, [Map<String, dynamic> extra = const {}]) => {...extra, if (version != null) 'version': version};
  Future<TaskEntity> _act(int id, String name, Map<String, dynamic> body) async => TaskEntity.fromJson(await _ds.action(id, name, body));

  @override
  Future<Paginated<TaskEntity>> list({int page = 1, String? status}) async {
    final r = await _ds.list({'page': page, if (status != null) 'status': status});
    return Paginated<TaskEntity>(
      items: r.data.map((e) => TaskEntity.fromJson(e as Map<String, dynamic>)).toList(),
      page: (r.meta['page'] as num?)?.toInt() ?? 1,
      perPage: (r.meta['per_page'] as num?)?.toInt() ?? 20,
      total: (r.meta['total'] as num?)?.toInt() ?? r.data.length,
    );
  }

  @override
  Future<TaskEntity> get(int id) async => TaskEntity.fromJson(await _ds.get(id));
  @override
  Future<TaskEntity> start(int id, {int? version}) => _act(id, 'start', _v(version));
  @override
  Future<TaskEntity> hold(int id, {int? version}) => _act(id, 'hold', _v(version));
  @override
  Future<TaskEntity> resume(int id, {int? version}) => _act(id, 'resume', _v(version));
  @override
  Future<TaskEntity> complete(int id, {required String resultSummary, int? version}) => _act(id, 'complete', _v(version, {'result_summary': resultSummary}));
}
