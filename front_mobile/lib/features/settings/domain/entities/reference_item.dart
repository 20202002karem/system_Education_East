import 'package:equatable/equatable.dart';

enum ReferenceResource { deviceCategories, requestTypes, taskTypes, intakeChannels }

String referenceResourceToWire(ReferenceResource r) => switch (r) {
      ReferenceResource.deviceCategories => 'device-categories',
      ReferenceResource.requestTypes => 'request-types',
      ReferenceResource.taskTypes => 'task-types',
      ReferenceResource.intakeChannels => 'intake-channels',
    };

/// Batch 2 §B12 — device-categories only has `name` (Appendices v1.0 binding
/// version, no is_active); task-types additionally has is_administrative /
/// secretary_assignable; the other two have is_active. All four share one
/// entity shape with nullable fields rather than four near-identical classes,
/// mirroring the web app's ReferenceItem type (instructions §9).
class ReferenceItemEntity extends Equatable {
  const ReferenceItemEntity({
    required this.id,
    required this.name,
    this.isActive,
    this.isAdministrative,
    this.secretaryAssignable,
  });

  final int id;
  final String name;
  final bool? isActive;
  final bool? isAdministrative;
  final bool? secretaryAssignable;

  @override
  List<Object?> get props => [id, name, isActive, isAdministrative, secretaryAssignable];
}
