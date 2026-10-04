import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_states.dart';
import '../cubit/audit_log_cubit.dart';
import '../cubit/audit_log_state.dart';
import 'chain_checks_page.dart';

/// Read-only by design — no edit/delete affordance exists anywhere on this
/// page (IN-12), matching the absence of any such route on the backend.
class AuditLogPage extends StatefulWidget {
  const AuditLogPage({super.key});

  @override
  State<AuditLogPage> createState() => _AuditLogPageState();
}

class _AuditLogPageState extends State<AuditLogPage> {
  @override
  void initState() {
    super.initState();
    context.read<AuditLogCubit>().load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('سجل التدقيق'),
        actions: [
          IconButton(
            icon: const Icon(Icons.verified_outlined),
            tooltip: 'فحوصات سلسلة التدقيق',
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const ChainChecksPage())),
          ),
        ],
      ),
      body: BlocBuilder<AuditLogCubit, AuditLogState>(
        builder: (context, state) {
          return switch (state) {
            AuditLogInitial() || AuditLogLoading() => const AppLoadingState(),
            AuditLogFailure(:final message) => AppErrorState(message: message, onRetry: () => context.read<AuditLogCubit>().load()),
            AuditLogSuccess(:final page) when page.items.isEmpty => const AppEmptyState(title: 'لا توجد سجلات مطابقة'),
            AuditLogSuccess(:final page) => ListView.separated(
                padding: const EdgeInsets.all(AppSpacing.s4),
                itemCount: page.items.length,
                separatorBuilder: (_, __) => const Divider(height: 1),
                itemBuilder: (context, i) {
                  final e = page.items[i];
                  return ListTile(
                    title: Text(e.action),
                    subtitle: Text('${e.entityType} #${e.entityId} · Actor #${e.actorId}\n${e.occurredAt.toIso8601String()} UTC'),
                    isThreeLine: true,
                    trailing: Text('#${e.seq}', style: const TextStyle(color: AppColors.textMuted)),
                  );
                },
              ),
          };
        },
      ),
    );
  }
}
