import '../../domain/entities/reference_item.dart';
import '../../domain/entities/setting.dart';
import '../../domain/repositories/settings_repository.dart';
import '../datasources/settings_remote_datasource.dart';
import '../models/reference_item_model.dart';
import '../models/setting_model.dart';

class SettingsRepositoryImpl implements SettingsRepository {
  SettingsRepositoryImpl(this._remote);
  final SettingsRemoteDataSource _remote;

  @override
  Future<List<SettingEntity>> listSettings() async {
    final list = await _remote.listSettings();
    return list.map((e) => SettingModel.fromJson(e as Map<String, dynamic>)).toList();
  }

  @override
  Future<SettingEntity> updateSetting(String key, String value) async =>
      SettingModel.fromJson(await _remote.updateSetting(key, value));

  @override
  Future<List<ReferenceItemEntity>> listReference(ReferenceResource resource) async {
    final list = await _remote.listReference(referenceResourceToWire(resource));
    return list.map((e) => ReferenceItemModel.fromJson(e as Map<String, dynamic>)).toList();
  }

  @override
  Future<ReferenceItemEntity> createReference(ReferenceResource resource, Map<String, dynamic> payload) async =>
      ReferenceItemModel.fromJson(await _remote.createReference(referenceResourceToWire(resource), payload));

  @override
  Future<ReferenceItemEntity> updateReference(ReferenceResource resource, int id, Map<String, dynamic> payload) async =>
      ReferenceItemModel.fromJson(await _remote.updateReference(referenceResourceToWire(resource), id, payload));
}
