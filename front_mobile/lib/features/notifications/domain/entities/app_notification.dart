import 'package:equatable/equatable.dart';

class AppNotificationEntity extends Equatable {
  const AppNotificationEntity({required this.id, required this.eventType, required this.message, this.sourceType, this.sourceId, this.readAt, this.createdAt});
  final int id;
  final String eventType;
  final String message;
  final String? sourceType;
  final int? sourceId;
  final String? readAt;
  final String? createdAt;

  bool get isRead => readAt != null;

  factory AppNotificationEntity.fromJson(Map<String, dynamic> j) => AppNotificationEntity(
        id: j['id'] as int,
        eventType: (j['event_type'] as String?) ?? '',
        message: (j['message'] as String?) ?? '',
        sourceType: j['source_type'] as String?,
        sourceId: (j['source_id'] as num?)?.toInt(),
        readAt: j['read_at'] as String?,
        createdAt: j['created_at'] as String?,
      );

  @override
  List<Object?> get props => [id, readAt, message];
}
