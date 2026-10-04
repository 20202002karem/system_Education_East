import 'package:equatable/equatable.dart';

enum SiteType { school, department, warehouse }
enum SiteStatus { active, archived }

SiteType siteTypeFromWire(String value) => switch (value) {
      'school' => SiteType.school,
      'department' => SiteType.department,
      'warehouse' => SiteType.warehouse,
      _ => throw ArgumentError('Unknown site type: $value'),
    };

String siteTypeToWire(SiteType type) => switch (type) {
      SiteType.school => 'school',
      SiteType.department => 'department',
      SiteType.warehouse => 'warehouse',
    };

/// Batch 2 §B1 (ORG-01). status is archive-only — IN-01, no physical delete,
/// mirrored 1:1 on the Flutter side: there is no deleteSite() anywhere.
class SiteEntity extends Equatable {
  const SiteEntity({
    required this.id,
    required this.type,
    required this.code,
    required this.nameAr,
    required this.status,
  });

  final int id;
  final SiteType type;
  final String code;
  final String nameAr;
  final SiteStatus status;

  @override
  List<Object?> get props => [id, type, code, nameAr, status];
}
