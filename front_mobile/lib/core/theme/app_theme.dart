import 'package:flutter/material.dart';

/// Design System tokens — mirrors the web app's tokens.css 1:1 so both
/// platforms present the same visual language (instructions §13).
class AppColors {
  static const bg = Color(0xFFF7F8FA);
  static const surface = Color(0xFFFFFFFF);
  static const border = Color(0xFFE2E5EA);
  static const text = Color(0xFF1A1F27);
  static const textMuted = Color(0xFF626B79);
  static const primary = Color(0xFF1D4ED8);
  static const primaryHover = Color(0xFF1E40AF);
  static const danger = Color(0xFFB91C1C);
  static const dangerBg = Color(0xFFFEF2F2);
  static const success = Color(0xFF15803D);
  static const successBg = Color(0xFFF0FDF4);
  static const warning = Color(0xFFB45309);
  static const warningBg = Color(0xFFFFFBEB);
  static const disabledBg = Color(0xFFEEF0F3);
}

class AppSpacing {
  static const s1 = 4.0, s2 = 8.0, s3 = 12.0, s4 = 16.0, s5 = 24.0, s6 = 32.0, s7 = 48.0;
}

class AppTheme {
  /// RTL is applied at the app root via Directionality/locale (see main.dart),
  /// not here — this only sets colors/typography per SRS §7 (واجهة عربية RTL).
  static ThemeData light() {
    final base = ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(seedColor: AppColors.primary, primary: AppColors.primary),
      scaffoldBackgroundColor: AppColors.bg,
      fontFamily: 'Tahoma',
    );
    return base.copyWith(
      appBarTheme: const AppBarTheme(backgroundColor: AppColors.surface, foregroundColor: AppColors.text, elevation: 0),
      cardTheme: CardThemeData(
        color: AppColors.surface,
        surfaceTintColor: AppColors.surface,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10), side: const BorderSide(color: AppColors.border)),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(6), borderSide: const BorderSide(color: AppColors.border)),
        filled: true,
        fillColor: AppColors.surface,
      ),
    );
  }
}
