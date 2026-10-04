import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../domain/entities/reference_item.dart';
import '../../domain/repositories/settings_repository.dart';
import 'reference_data_state.dart';

class ReferenceDataCubit extends Cubit<ReferenceDataState> {
  ReferenceDataCubit(this._repository) : super(const ReferenceDataInitial());
  final SettingsRepository _repository;

  Future<void> load(ReferenceResource resource) async {
    emit(const ReferenceDataLoading());
    try {
      final items = await _repository.listReference(resource);
      emit(ReferenceDataSuccess(resource, items));
    } on ApiException catch (e) {
      emit(ReferenceDataFailure(e.message));
    }
  }

  Future<void> create(String name) async {
    final current = state;
    if (current is! ReferenceDataSuccess) return;
    final payload = <String, dynamic>{'name': name};
    if (current.resource == ReferenceResource.taskTypes) {
      payload['is_administrative'] = false;
      payload['secretary_assignable'] = false;
    } else if (current.resource != ReferenceResource.deviceCategories) {
      payload['is_active'] = true;
    }
    await _repository.createReference(current.resource, payload);
    await load(current.resource);
  }

  Future<void> toggleActive(ReferenceItemEntity item) async {
    final current = state;
    if (current is! ReferenceDataSuccess) return;
    await _repository.updateReference(current.resource, item.id, {'name': item.name, 'is_active': !(item.isActive ?? false)});
    await load(current.resource);
  }
}
