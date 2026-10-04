import 'package:equatable/equatable.dart';

/// Batch 2 §B9 / M1 criterion 5 — results of the daily hash-chain verification job.
class AuditChainCheckEntity extends Equatable {
  const AuditChainCheckEntity({required this.id, required this.runAt, required this.fromSeq, required this.toSeq, required this.result});

  final int id;
  final DateTime runAt;
  final int fromSeq;
  final int toSeq;
  final String result; // 'ok' | 'broken'

  bool get isOk => result == 'ok';

  @override
  List<Object?> get props => [id, runAt, fromSeq, toSeq, result];
}
