import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../domain/repositories/settings_repository.dart';
import 'settings_state.dart';

/// Batch 3 §3.6 — each update writes a settings_history row server-side.
class SettingsCubit extends Cubit<SettingsState> {
  SettingsCubit(this._repository) : super(const SettingsInitial());
  final SettingsRepository _repository;

  Future<void> load() async {
    emit(const SettingsLoading());
    try {
      final settings = await _repository.listSettings();
      emit(SettingsSuccess(settings));
    } on ApiException catch (e) {
      emit(SettingsFailure(e.message));
    }
  }

  Future<void> update(String key, String value) async {
    final current = state;
    if (current is! SettingsSuccess) return;
    emit(current.copyWith(savingKey: key));
    try {
      await _repository.updateSetting(key, value);
      final settings = await _repository.listSettings();
      emit(SettingsSuccess(settings));
    } on ApiException {
      emit(current.copyWith(savingKey: null));
      rethrow;
    }
  }
}
