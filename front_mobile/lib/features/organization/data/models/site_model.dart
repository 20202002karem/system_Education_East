import '../../domain/entities/site.dart';

class SiteModel {
  static SiteEntity fromJson(Map<String, dynamic> json) {
    return SiteEntity(
      id: json['id'] as int,
      type: siteTypeFromWire(json['type'] as String),
      code: json['code'] as String,
      nameAr: json['name_ar'] as String,
      status: json['status'] == 'active' ? SiteStatus.active : SiteStatus.archived,
    );
  }
}
