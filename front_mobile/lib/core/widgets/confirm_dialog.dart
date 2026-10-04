import 'package:flutter/material.dart';
import 'app_button.dart';

/// Confirmation dialog for sensitive operations (instructions §4/§17: disable
/// user, archive site, revoke permission, terminate sessions, etc.).
Future<bool> showConfirmDialog(
  BuildContext context, {
  required String title,
  String? description,
  String confirmLabel = 'تأكيد',
  bool danger = false,
}) async {
  final result = await showDialog<bool>(
    context: context,
    builder: (ctx) => AlertDialog(
      title: Text(title),
      content: description != null ? Text(description) : null,
      actions: [
        AppButton(label: 'إلغاء', variant: AppButtonVariant.secondary, onPressed: () => Navigator.of(ctx).pop(false)),
        AppButton(
          label: confirmLabel,
          variant: danger ? AppButtonVariant.danger : AppButtonVariant.primary,
          onPressed: () => Navigator.of(ctx).pop(true),
        ),
      ],
    ),
  );
  return result ?? false;
}
