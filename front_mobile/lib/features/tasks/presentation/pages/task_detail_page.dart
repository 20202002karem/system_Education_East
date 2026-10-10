import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_snackbar.dart';
import '../../../../core/widgets/app_states.dart';
import '../../../../core/widgets/text_prompt_dialog.dart';
import '../../../auth/presentation/cubit/auth_cubit.dart';
import '../../../auth/presentation/cubit/auth_state.dart';
import '../cubit/task_detail_cubit.dart';

class TaskDetailPage extends StatelessWidget {
  const TaskDetailPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<TaskDetailCubit, TaskDetailState>(
      listener: (context, s) {
        if (s is TaskDetailLoaded) {
          if (s.notice != null) showAppSnackBar(context, s.notice!, tone: SnackTone.success);
          if (s.error != null) showAppSnackBar(context, s.error!, tone: SnackTone.error);
        }
      },
      builder: (context, s) => Scaffold(
        appBar: AppBar(title: Text(s is TaskDetailLoaded ? s.task.refNo : 'تفاصيل المهمة')),
        body: switch (s) {
          TaskDetailLoading() => const AppLoadingState(),
          TaskDetailFailure(:final message) => AppErrorState(message: message, onRetry: context.read<TaskDetailCubit>().load),
          TaskDetailLoaded() => _body(context, s),
        },
      ),
    );
  }

  Widget _body(BuildContext context, TaskDetailLoaded s) {
    final cubit = context.read<TaskDetailCubit>();
    final auth = context.read<AuthCubit>().state;
    final t = s.task;
    final isAssignee = auth is AuthAuthenticated && t.assigneeId == auth.user.id;
    return ListView(padding: const EdgeInsets.all(AppSpacing.s4), children: [
      Card(
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.s3),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(t.title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Text('الحالة: ${t.statusLabel}'),
            if (t.description != null) Text(t.description!),
            if (t.resultSummary != null) Text('النتيجة: ${t.resultSummary}'),
          ]),
        ),
      ),
      const SizedBox(height: AppSpacing.s3),
      Wrap(spacing: 8, runSpacing: 8, children: [
        if (isAssignee && t.status == 'assigned') AppButton(label: 'بدء', loading: s.busy, onPressed: cubit.start),
        if (isAssignee && t.status == 'in_progress') AppButton(label: 'تعليق', variant: AppButtonVariant.secondary, loading: s.busy, onPressed: cubit.hold),
        if (isAssignee && t.status == 'held') AppButton(label: 'استئناف', loading: s.busy, onPressed: cubit.resume),
        if (isAssignee && t.status == 'in_progress')
          AppButton(label: 'إكمال', loading: s.busy, onPressed: () async {
            final v = await showPromptDialog(context, title: 'إكمال المهمة', fields: const [PromptField('summary', 'ملخص النتيجة', multiline: true)]);
            if (v != null) cubit.complete(v['summary']!);
          }),
      ]),
    ]);
  }
}
