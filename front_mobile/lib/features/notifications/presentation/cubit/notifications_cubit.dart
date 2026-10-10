import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/utils/paginated.dart';
import '../../domain/entities/app_notification.dart';
import '../../domain/repositories/notifications_repository.dart';

sealed class NotificationsState extends Equatable {
  const NotificationsState();
  @override
  List<Object?> get props => [];
}

class NotificationsInitial extends NotificationsState { const NotificationsInitial(); }
class NotificationsLoading extends NotificationsState { const NotificationsLoading(); }

class NotificationsSuccess extends NotificationsState {
  const NotificationsSuccess(this.page);
  final Paginated<AppNotificationEntity> page;
  @override
  List<Object?> get props => [page.items, page.page, page.total];
}

class NotificationsFailure extends NotificationsState {
  const NotificationsFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}

class NotificationsCubit extends Cubit<NotificationsState> {
  NotificationsCubit(this._repository) : super(const NotificationsInitial());
  final NotificationsRepository _repository;

  Future<void> load({int page = 1}) async {
    emit(const NotificationsLoading());
    try {
      emit(NotificationsSuccess(await _repository.list(page: page)));
    } on ApiException catch (e) {
      emit(NotificationsFailure(e.message));
    }
  }

  Future<void> markRead(AppNotificationEntity n) async {
    if (n.isRead) return;
    try {
      await _repository.markRead(n.id);
      final cur = state;
      await load(page: cur is NotificationsSuccess ? cur.page.page : 1);
    } on ApiException catch (e) {
      emit(NotificationsFailure(e.message));
    }
  }
}
