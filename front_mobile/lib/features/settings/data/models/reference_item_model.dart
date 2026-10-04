import '../../domain/entities/reference_item.dart';

class ReferenceItemModel {
  static ReferenceItemEntity fromJson(Map<String, dynamic> json) {
    return ReferenceItemEntity(
      id: json['id'] as int,
      name: json['name'] as String,
      isActive: json['is_active'] as bool?,
      isAdministrative: json['is_administrative'] as bool?,
      secretaryAssignable: json['secretary_assignable'] as bool?,
    );
  }
}
