import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_states.dart';
import '../../domain/entities/task.dart';
import '../../domain/repositories/tasks_repository.dart';
import '../cubit/task_detail_cubit.dart';
import '../cubit/tasks_list_cubit.dart';
import 'task_detail_page.dart';

/// "مهامي": the server returns only tasks the caller may read (assigned; engineer also by site scope).
class TasksListPage extends StatefulWidget {
  const TasksListPage({super.key});
  @override
  State<TasksListPage> createState() => _TasksListPageState();
}

class _TasksListPageState extends State<TasksListPage> {
  @override
  void initState() {
    super.initState();
    context.read<TasksListCubit>().load();
  }

  Future<void> _open(int id) async {
    final repo = context.read<TasksRepository>();
    await Navigator.of(context).push(MaterialPageRoute(
      builder: (_) => BlocProvider(create: (_) => TaskDetailCubit(repo, id)..load(), child: const TaskDetailPage()),
    ));
    if (mounted) context.read<TasksListCubit>().load();
  }

  @override
  Widget build(BuildContext context) {
    final cubit = context.read<TasksListCubit>();
    return Scaffold(
      appBar: AppBar(title: const Text('مهامي')),
      body: Column(children: [
        Padding(
          padding: const EdgeInsets.all(AppSpacing.s3),
          child: DropdownButton<String?>(
            value: cubit.status,
            hint: const Text('الحالة'),
            items: [
              const DropdownMenuItem<String?>(value: null, child: Text('الكل')),
              for (final e in taskStatusLabels.entries) DropdownMenuItem<String?>(value: e.key, child: Text(e.value)),
            ],
            onChanged: (v) { setState(() => cubit.status = v); cubit.load(); },
          ),
        ),
        Expanded(
          child: BlocBuilder<TasksListCubit, TasksListState>(
            builder: (context, state) => switch (state) {
              TasksListInitial() || TasksListLoading() => const AppLoadingState(),
              TasksListFailure(:final message) => AppErrorState(message: message, onRetry: cubit.load),
              TasksListSuccess(:final page) when page.items.isEmpty => const AppEmptyState(title: 'لا توجد مهام'),
              TasksListSuccess(:final page) => ListView(children: [
                  for (final t in page.items)
                    Card(child: ListTile(title: Text('${t.refNo} — ${t.title}'), subtitle: Text(t.statusLabel), onTap: () => _open(t.id))),
                  Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                    IconButton(onPressed: page.hasPreviousPage ? () => cubit.load(page: page.page - 1) : null, icon: const Icon(Icons.chevron_right)),
                    Text('${page.page} / ${page.totalPages}'),
                    IconButton(onPressed: page.hasNextPage ? () => cubit.load(page: page.page + 1) : null, icon: const Icon(Icons.chevron_left)),
                  ]),
                ]),
            },
          ),
        ),
      ]),
    );
  }
}
