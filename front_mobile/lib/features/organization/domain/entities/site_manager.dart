import 'package:equatable/equatable.dart';

/// Batch 2 §B2 / Batch 3 §3.5 — `to == null` means currently active.
/// Assigning a new manager auto-closes the previously active row server-side.
class SiteManagerEntity extends Equatable {
  const SiteManagerEntity({
    required this.id,
    required this.siteId,
    required this.userId,
    required this.from,
    this.to,
  });

  final int id;
  final int siteId;
  final int userId;
  final DateTime from;
  final DateTime? to;

  bool get isActive => to == null;

  @override
  List<Object?> get props => [id, siteId, userId, from, to];
}
