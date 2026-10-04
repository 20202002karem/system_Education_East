import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_states.dart';
import '../cubit/chain_checks_cubit.dart';
import '../cubit/chain_checks_state.dart';

class ChainChecksPage extends StatefulWidget {
  const ChainChecksPage({super.key});

  @override
  State<ChainChecksPage> createState() => _ChainChecksPageState();
}

class _ChainChecksPageState extends State<ChainChecksPage> {
  @override
  void initState() {
    super.initState();
    context.read<ChainChecksCubit>().load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('فحوصات سلسلة التدقيق')),
      body: BlocBuilder<ChainChecksCubit, ChainChecksState>(
        builder: (context, state) {
          return switch (state) {
            ChainChecksInitial() || ChainChecksLoading() => const AppLoadingState(),
            ChainChecksFailure(:final message) => AppErrorState(message: message, onRetry: () => context.read<ChainChecksCubit>().load()),
            ChainChecksSuccess(:final page) when page.items.isEmpty =>
              const AppEmptyState(title: 'لا توجد نتائج فحص بعد', hint: 'يعمل الفحص يوميًا الساعة 02:00 UTC'),
            ChainChecksSuccess(:final page) => ListView.builder(
                padding: const EdgeInsets.all(AppSpacing.s4),
                itemCount: page.items.length,
                itemBuilder: (context, i) {
                  final c = page.items[i];
                  return Card(
                    child: ListTile(
                      title: Text(c.runAt.toIso8601String()),
                      subtitle: Text('من ${c.fromSeq} إلى ${c.toSeq}'),
                      trailing: Chip(
                        label: Text(c.isOk ? 'سليمة' : 'مكسورة'),
                        backgroundColor: c.isOk ? AppColors.successBg : AppColors.dangerBg,
                      ),
                    ),
                  );
                },
              ),
          };
        },
      ),
    );
  }
}
