import 'package:flutter/material.dart';
import '../theme/app_theme.dart';
import 'app_button.dart';

/// Loading / Empty / Error states — used consistently across every list
/// screen, mirroring the web app's components/states.tsx (instructions §9).

class AppLoadingState extends StatelessWidget {
  const AppLoadingState({super.key, this.label = 'جارٍ التحميل…'});
  final String label;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.s6),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const CircularProgressIndicator(),
          const SizedBox(height: AppSpacing.s3),
          Text(label, style: const TextStyle(color: AppColors.textMuted)),
        ]),
      ),
    );
  }
}

class AppEmptyState extends StatelessWidget {
  const AppEmptyState({super.key, required this.title, this.hint});
  final String title;
  final String? hint;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.s6),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Text(title, style: const TextStyle(fontSize: 18)),
          if (hint != null) Text(hint!, style: const TextStyle(color: AppColors.textMuted)),
        ]),
      ),
    );
  }
}

class AppErrorState extends StatelessWidget {
  const AppErrorState({super.key, required this.message, this.onRetry});
  final String message;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.s4),
      margin: const EdgeInsets.all(AppSpacing.s4),
      decoration: BoxDecoration(color: AppColors.dangerBg, borderRadius: BorderRadius.circular(8)),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(message, style: const TextStyle(color: AppColors.danger)),
        if (onRetry != null) ...[
          const SizedBox(height: AppSpacing.s2),
          AppButton(label: 'إعادة المحاولة', variant: AppButtonVariant.ghost, onPressed: onRetry),
        ],
      ]),
    );
  }
}
