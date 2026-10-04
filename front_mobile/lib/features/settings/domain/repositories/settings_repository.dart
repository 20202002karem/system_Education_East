import '../entities/reference_item.dart';
import '../entities/setting.dart';

/// Batch 3 §2.4 — Settings & Reference Data, chairman-only.
abstract class SettingsRepository {
  Future<List<SettingEntity>> listSettings();
  Future<SettingEntity> updateSetting(String key, String value);

  Future<List<ReferenceItemEntity>> listReference(ReferenceResource resource);
  Future<ReferenceItemEntity> createReference(ReferenceResource resource, Map<String, dynamic> payload);
  Future<ReferenceItemEntity> updateReference(ReferenceResource resource, int id, Map<String, dynamic> payload);
}
