import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

enum SnackTone { success, error, info }

void showAppSnackBar(BuildContext context, String message, {SnackTone tone = SnackTone.info}) {
  final color = switch (tone) {
    SnackTone.success => AppColors.success,
    SnackTone.error => AppColors.danger,
    SnackTone.info => AppColors.text,
  };
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(
      content: Text(message),
      backgroundColor: color,
      behavior: SnackBarBehavior.floating,
    ),
  );
}
