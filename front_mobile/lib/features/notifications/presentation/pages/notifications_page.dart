import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/widgets/app_states.dart';
import '../cubit/notifications_cubit.dart';

class NotificationsPage extends StatefulWidget {
  const NotificationsPage({super.key});
  @override
  State<NotificationsPage> createState() => _NotificationsPageState();
}

class _NotificationsPageState extends State<NotificationsPage> {
  @override
  void initState() {
    super.initState();
    context.read<NotificationsCubit>().load();
  }

  @override
  Widget build(BuildContext context) {
    final cubit = context.read<NotificationsCubit>();
    return Scaffold(
      appBar: AppBar(title: const Text('الإشعارات')),
      body: BlocBuilder<NotificationsCubit, NotificationsState>(
        builder: (context, state) => switch (state) {
          NotificationsInitial() || NotificationsLoading() => const AppLoadingState(),
          NotificationsFailure(:final message) => AppErrorState(message: message, onRetry: cubit.load),
          NotificationsSuccess(:final page) when page.items.isEmpty => const AppEmptyState(title: 'لا توجد إشعارات'),
          NotificationsSuccess(:final page) => ListView(children: [
              for (final n in page.items)
                Card(
                  child: ListTile(
                    leading: Icon(n.isRead ? Icons.notifications_none : Icons.notifications_active),
                    title: Text(n.message, style: TextStyle(fontWeight: n.isRead ? FontWeight.normal : FontWeight.bold)),
                    subtitle: Text(n.createdAt ?? ''),
                    onTap: () => cubit.markRead(n),
                  ),
                ),
              Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                IconButton(onPressed: page.hasPreviousPage ? () => cubit.load(page: page.page - 1) : null, icon: const Icon(Icons.chevron_right)),
                Text('${page.page} / ${page.totalPages}'),
                IconButton(onPressed: page.hasNextPage ? () => cubit.load(page: page.page + 1) : null, icon: const Icon(Icons.chevron_left)),
              ]),
            ]),
        },
      ),
    );
  }
}
