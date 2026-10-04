import '../../domain/entities/setting.dart';

class SettingModel {
  static SettingEntity fromJson(Map<String, dynamic> json) {
    return SettingEntity(key: json['key'] as String, value: json['value'] as String?, updatedBy: json['updated_by'] as int?);
  }
}
