import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_snackbar.dart';
import '../../../../core/widgets/app_states.dart';
import '../../../../core/widgets/text_prompt_dialog.dart';
import '../../../auth/presentation/cubit/auth_cubit.dart';
import '../../../auth/presentation/cubit/auth_state.dart';
import '../../../users/domain/entities/user.dart';
import '../../domain/entities/service_request.dart';
import '../cubit/request_detail_cubit.dart';

/// Request detail with the actions mobile supports (Batch 3 §26). Buttons are UX only — the server
/// re-checks role, scope, assignee and state on every call (403/404/409).
class RequestDetailPage extends StatelessWidget {
  const RequestDetailPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<RequestDetailCubit, RequestDetailState>(
      listener: (context, s) {
        if (s is RequestDetailLoaded) {
          if (s.notice != null) showAppSnackBar(context, s.notice!, tone: SnackTone.success);
          if (s.error != null) showAppSnackBar(context, s.error!, tone: SnackTone.error);
        }
      },
      builder: (context, s) => Scaffold(
        appBar: AppBar(title: Text(s is RequestDetailLoaded ? s.request.refNo : 'تفاصيل الطلب')),
        body: switch (s) {
          RequestDetailLoading() => const AppLoadingState(),
          RequestDetailFailure(:final message) => AppErrorState(message: message, onRetry: context.read<RequestDetailCubit>().load),
          RequestDetailLoaded() => _Body(state: s),
        },
      ),
    );
  }
}

class _Body extends StatelessWidget {
  const _Body({required this.state});
  final RequestDetailLoaded state;

  @override
  Widget build(BuildContext context) {
    final cubit = context.read<RequestDetailCubit>();
    final auth = context.read<AuthCubit>().state;
    final user = auth is AuthAuthenticated ? auth.user : null;
    final r = state.request;
    final status = r.status;
    final isAssignee = user != null && r.cycle?.assigneeUserId == user.id;
    final isRequester = user != null && r.requesterUserId == user.id;
    final role = user?.role;

    final canStart = isAssignee && status == 'assigned';
    final canHold = isAssignee && status == 'in_progress';
    final canResume = isAssignee && status == 'held';
    final canClose = isAssignee && status == 'in_progress';
    const nonTerminal = ['new', 'triaged', 'assigned', 'in_progress', 'held', 'reopened_pending_assignment'];
    final canCancel = status != null && nonTerminal.contains(status) &&
        (role == UserRole.chairman ||
            ((role == UserRole.secretary || (role == UserRole.schoolManager && isRequester)) && (status == 'new' || status == 'triaged')));
    final canReopen = status == 'closed' && (role == UserRole.chairman || (role == UserRole.schoolManager && isRequester));

    return ListView(padding: const EdgeInsets.all(AppSpacing.s4), children: [
      Card(
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.s3),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(r.statusLabel, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Text('الموقع: ${r.originSiteName ?? '#${r.originSiteId}'}'),
            Text('المسؤول: ${r.cycle?.assigneeName ?? '—'}'),
            Text('الدورة: ${r.cycle?.cycleNo ?? 1}'),
            const SizedBox(height: 8),
            Text(r.description),
          ]),
        ),
      ),
      const SizedBox(height: AppSpacing.s3),
      Wrap(spacing: 8, runSpacing: 8, children: [
        if (canStart) AppButton(label: 'بدء التنفيذ', loading: state.busy, onPressed: cubit.start),
        if (canHold)
          AppButton(label: 'تعليق', variant: AppButtonVariant.secondary, loading: state.busy, onPressed: () async {
            final v = await showPromptDialog(context, title: 'تعليق الطلب', fields: const [PromptField('reason', 'سبب التعليق', required: false)]);
            if (v != null) cubit.hold(v['reason']!);
          }),
        if (canResume) AppButton(label: 'استئناف', loading: state.busy, onPressed: cubit.resume),
        if (canClose)
          AppButton(label: 'إغلاق', loading: state.busy, onPressed: () async {
            final v = await showPromptDialog(context, title: 'إغلاق الطلب', fields: const [
              PromptField('action', 'الإجراء المنفّذ', multiline: true),
              PromptField('result', 'النتيجة', multiline: true),
              PromptField('effort', 'الجهد (بالدقائق)', numeric: true),
            ]);
            if (v != null) cubit.close(action: v['action']!, result: v['result']!, effort: int.parse(v['effort']!));
          }),
        if (canCancel)
          AppButton(label: 'إلغاء الطلب', variant: AppButtonVariant.danger, loading: state.busy, onPressed: () => _cancel(context, cubit, status == 'in_progress' || status == 'held')),
        if (canReopen)
          AppButton(label: 'اعتراض / إعادة فتح', variant: AppButtonVariant.secondary, loading: state.busy, onPressed: () async {
            final v = await showPromptDialog(context, title: 'إعادة فتح الطلب', fields: const [PromptField('reason', 'السبب', multiline: true)]);
            if (v != null) cubit.reopen(v['reason']!);
          }),
      ]),
      const Divider(height: 32),
      Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
        const Text('الملاحظات', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        TextButton.icon(
          icon: const Icon(Icons.add_comment_outlined),
          label: const Text('إضافة ملاحظة'),
          onPressed: () async {
            final v = await showPromptDialog(context, title: 'ملاحظة جديدة', fields: const [PromptField('body', 'النص', multiline: true)]);
            if (v != null) cubit.addNote(role == UserRole.schoolManager ? 'external' : 'internal', v['body']!);
          },
        ),
      ]),
      if (state.notes.isEmpty) const AppEmptyState(title: 'لا توجد ملاحظات'),
      for (final n in state.notes)
        Card(child: ListTile(title: Text(n.body), subtitle: Text(n.visibility == 'external' ? 'ظاهرة لمقدّم الطلب' : 'داخلية'))),
    ]);
  }

  Future<void> _cancel(BuildContext context, RequestDetailCubit cubit, bool afterStart) async {
    final category = await showModalBottomSheet<String>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          for (final e in cancelCategoryLabels.entries) ListTile(title: Text(e.value), onTap: () => Navigator.of(ctx).pop(e.key)),
        ]),
      ),
    );
    if (category == null || !context.mounted) return;
    final v = await showPromptDialog(context, title: 'إلغاء الطلب', fields: [
      const PromptField('text', 'سبب الإلغاء', multiline: true),
      PromptField('work', 'ملخص العمل المنجز', required: afterStart, multiline: true),
    ]);
    if (v != null) cubit.cancel(category: category, text: v['text']!, workDone: v['work']);
  }
}
