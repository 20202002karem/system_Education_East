import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

enum AppButtonVariant { primary, secondary, danger, ghost }

class AppButton extends StatelessWidget {
  const AppButton({super.key, required this.label, this.onPressed, this.variant = AppButtonVariant.primary, this.loading = false});

  final String label;
  final VoidCallback? onPressed;
  final AppButtonVariant variant;
  final bool loading;

  @override
  Widget build(BuildContext context) {
    final disabled = onPressed == null || loading;
    final Color bg;
    final Color fg;
    switch (variant) {
      case AppButtonVariant.primary:
        bg = AppColors.primary; fg = Colors.white; break;
      case AppButtonVariant.secondary:
        bg = AppColors.surface; fg = AppColors.text; break;
      case AppButtonVariant.danger:
        bg = AppColors.danger; fg = Colors.white; break;
      case AppButtonVariant.ghost:
        bg = Colors.transparent; fg = AppColors.primary; break;
    }

    return ElevatedButton(
      onPressed: disabled ? null : onPressed,
      style: ElevatedButton.styleFrom(
        backgroundColor: bg,
        foregroundColor: fg,
        side: variant == AppButtonVariant.secondary ? const BorderSide(color: AppColors.border) : BorderSide.none,
        elevation: variant == AppButtonVariant.ghost ? 0 : 1,
      ),
      child: loading
          ? const SizedBox(height: 16, width: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
          : Text(label),
    );
  }
}
