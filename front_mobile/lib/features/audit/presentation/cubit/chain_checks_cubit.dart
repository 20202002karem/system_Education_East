import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../domain/repositories/audit_repository.dart';
import 'chain_checks_state.dart';

/// M1 criterion 5 — results of the daily audit:verify-chain job (backend).
class ChainChecksCubit extends Cubit<ChainChecksState> {
  ChainChecksCubit(this._repository) : super(const ChainChecksInitial());
  final AuditRepository _repository;

  Future<void> load() async {
    emit(const ChainChecksLoading());
    try {
      final result = await _repository.listChainChecks();
      emit(ChainChecksSuccess(result));
    } on ApiException catch (e) {
      emit(ChainChecksFailure(e.message));
    }
  }
}
