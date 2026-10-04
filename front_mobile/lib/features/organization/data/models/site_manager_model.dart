import '../../domain/entities/site_manager.dart';

class SiteManagerModel {
  static SiteManagerEntity fromJson(Map<String, dynamic> json) {
    return SiteManagerEntity(
      id: json['id'] as int,
      siteId: json['site_id'] as int,
      userId: json['user_id'] as int,
      from: DateTime.parse(json['from'] as String),
      to: json['to'] != null ? DateTime.parse(json['to'] as String) : null,
    );
  }
}
