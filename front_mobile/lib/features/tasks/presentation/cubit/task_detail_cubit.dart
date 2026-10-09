import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../domain/entities/task.dart';
import '../../domain/repositories/tasks_repository.dart';

sealed class TaskDetailState extends Equatable {
  const TaskDetailState();
  @override
  List<Object?> get props => [];
}

class TaskDetailLoading extends TaskDetailState { const TaskDetailLoading(); }

class TaskDetailLoaded extends TaskDetailState {
  const TaskDetailLoaded(this.task, {this.busy = false, this.notice, this.error});
  final TaskEntity task;
  final bool busy;
  final String? notice;
  final String? error;
  @override
  List<Object?> get props => [task, busy, notice, error];
}

class TaskDetailFailure extends TaskDetailState {
  const TaskDetailFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}

class TaskDetailCubit extends Cubit<TaskDetailState> {
  TaskDetailCubit(this._repository, this.id) : super(const TaskDetailLoading());
  final TasksRepository _repository;
  final int id;

  Future<void> load() async {
    emit(const TaskDetailLoading());
    try {
      emit(TaskDetailLoaded(await _repository.get(id)));
    } on ApiException catch (e) {
      emit(TaskDetailFailure(e.message));
    }
  }

  Future<void> _run(Future<TaskEntity> Function(int? version) fn, String ok) async {
    final cur = state;
    if (cur is! TaskDetailLoaded || cur.busy) return;
    emit(TaskDetailLoaded(cur.task, busy: true));
    try {
      emit(TaskDetailLoaded(await fn(cur.task.version), notice: ok));
    } on ApiException catch (e) {
      if (e.statusCode == 409) {
        try {
          emit(TaskDetailLoaded(await _repository.get(id), error: e.message));
          return;
        } on ApiException catch (_) {}
      }
      emit(TaskDetailLoaded(cur.task, error: e.message));
    }
  }

  Future<void> start() => _run((v) => _repository.start(id, version: v), 'بدأ تنفيذ المهمة');
  Future<void> hold() => _run((v) => _repository.hold(id, version: v), 'عُلِّقت المهمة');
  Future<void> resume() => _run((v) => _repository.resume(id, version: v), 'استُؤنفت المهمة');
  Future<void> complete(String summary) => _run((v) => _repository.complete(id, resultSummary: summary, version: v), 'اكتملت المهمة');
}
