import 'package:equatable/equatable.dart';

/// D-21 / MD-01 tunables (Batch 2 §B7). Every update writes a
/// settings_history row server-side (Batch 3 §3.6) — nothing to model here,
/// the write endpoint is fire-and-forget from the client's perspective.
class SettingEntity extends Equatable {
  const SettingEntity({required this.key, required this.value, this.updatedBy});

  final String key;
  final String? value;
  final int? updatedBy;

  @override
  List<Object?> get props => [key, value, updatedBy];
}
