import 'package:equatable/equatable.dart';

const taskStatusLabels = <String, String>{
  'new': 'جديدة',
  'assigned': 'مُسنَدة',
  'in_progress': 'قيد التنفيذ',
  'held': 'معلّقة',
  'completed': 'مكتملة',
  'cancelled': 'ملغاة',
};

class TaskEntity extends Equatable {
  const TaskEntity({required this.id, required this.refNo, required this.title, this.description, required this.status, this.assigneeId, this.dueAt, this.resultSummary, this.version});
  final int id;
  final String refNo;
  final String title;
  final String? description;
  final String status;
  final int? assigneeId;
  final String? dueAt;
  final String? resultSummary;
  final int? version;

  String get statusLabel => taskStatusLabels[status] ?? status;

  factory TaskEntity.fromJson(Map<String, dynamic> j) => TaskEntity(
        id: j['id'] as int,
        refNo: j['ref_no'] as String,
        title: (j['title'] as String?) ?? '',
        description: j['description'] as String?,
        status: j['status'] as String,
        assigneeId: j['assignee_id'] as int?,
        dueAt: j['due_at'] as String?,
        resultSummary: j['result_summary'] as String?,
        version: (j['version'] as num?)?.toInt(),
      );

  @override
  List<Object?> get props => [id, refNo, status, assigneeId, version, resultSummary];
}
