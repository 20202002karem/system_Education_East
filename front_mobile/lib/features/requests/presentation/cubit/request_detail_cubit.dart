import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../domain/entities/service_request.dart';
import '../../domain/repositories/requests_repository.dart';

sealed class RequestDetailState extends Equatable {
  const RequestDetailState();
  @override
  List<Object?> get props => [];
}

class RequestDetailLoading extends RequestDetailState { const RequestDetailLoading(); }

class RequestDetailLoaded extends RequestDetailState {
  const RequestDetailLoaded(this.request, this.notes, {this.busy = false, this.notice, this.error});
  final ServiceRequestEntity request;
  final List<RequestNoteEntity> notes;
  final bool busy;
  final String? notice;
  final String? error;
  @override
  List<Object?> get props => [request, notes, busy, notice, error];
}

class RequestDetailFailure extends RequestDetailState {
  const RequestDetailFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}

/// Transition actions always send the cycle `version` (optimistic locking, M1 convention);
/// a 409 surfaces the server message and the screen reloads the fresh state.
class RequestDetailCubit extends Cubit<RequestDetailState> {
  RequestDetailCubit(this._repository, this.id) : super(const RequestDetailLoading());
  final RequestsRepository _repository;
  final int id;

  Future<void> load() async {
    emit(const RequestDetailLoading());
    try {
      final r = await _repository.get(id);
      final n = await _repository.notes(id);
      emit(RequestDetailLoaded(r, n.items));
    } on ApiException catch (e) {
      emit(RequestDetailFailure(e.message));
    }
  }

  Future<void> _run(Future<ServiceRequestEntity> Function(int? version) fn, String okMessage) async {
    final cur = state;
    if (cur is! RequestDetailLoaded || cur.busy) return;
    emit(RequestDetailLoaded(cur.request, cur.notes, busy: true));
    try {
      final updated = await fn(cur.request.cycle?.version);
      final fresh = await _repository.get(updated.id);
      emit(RequestDetailLoaded(fresh, cur.notes, notice: okMessage));
    } on ApiException catch (e) {
      if (e.statusCode == 409) {
        try {
          final fresh = await _repository.get(id);
          emit(RequestDetailLoaded(fresh, cur.notes, error: e.message));
          return;
        } on ApiException catch (_) {}
      }
      emit(RequestDetailLoaded(cur.request, cur.notes, error: e.message));
    }
  }

  Future<void> start() => _run((v) => _repository.start(id, version: v), 'بدأ التنفيذ');
  Future<void> hold(String reason) => _run((v) => _repository.hold(id, reason: reason, version: v), 'عُلِّق الطلب');
  Future<void> resume() => _run((v) => _repository.resume(id, version: v), 'استُؤنف الطلب');
  Future<void> close({required String action, required String result, required int effort}) =>
      _run((v) => _repository.close(id, action: action, result: result, effortMinutes: effort, version: v), 'أُغلق الطلب');
  Future<void> cancel({required String category, required String text, String? workDone}) =>
      _run((v) => _repository.cancel(id, category: category, text: text, workDone: workDone, version: v), 'أُلغي الطلب');
  Future<void> reopen(String reason) => _run((_) => _repository.reopen(id, reason: reason), 'أُعيد فتح الطلب');

  Future<void> addNote(String visibility, String body) async {
    final cur = state;
    if (cur is! RequestDetailLoaded || cur.busy) return;
    try {
      await _repository.addNote(id, visibility: visibility, body: body);
      final n = await _repository.notes(id);
      emit(RequestDetailLoaded(cur.request, n.items, notice: 'أُضيفت الملاحظة'));
    } on ApiException catch (e) {
      emit(RequestDetailLoaded(cur.request, cur.notes, error: e.message));
    }
  }
}
