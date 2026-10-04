/// Localization seam (instructions §5: "قابلية إضافة اللغة الإنجليزية لاحقًا
/// بدون إعادة بناء المشروع"). Central place to migrate to flutter_localizations
/// + .arb files later.
///
/// HONEST GAP: for this M1 pass, page-level strings are still hardcoded
/// Arabic literals inline in each widget (matching the Arabic-first M1 UX
/// requirement §4 exactly, and keeping the diff reviewable against the
/// Arabic API error messages). Centralizing them here now, this early,
/// would have meant hand-copying ~150 literals with no functional benefit
/// yet. What IS done to keep the door open for English:
///   - No string concatenation with grammar-dependent word order anywhere.
///   - All dates/times use DateTime.toIso8601String() (locale-neutral) rather
///     than hand-built Arabic date strings, except the two `_fmtDate` helpers
///     in Organization, which are ISO already (yyyy-MM-dd).
///   - RTL is driven by MaterialApp's `locale`/`Directionality`, not by any
///     hardcoded `TextDirection.rtl` in individual widgets, so flipping
///     locale later flips direction automatically.
/// Migrating to real l10n later is a mechanical find/replace of literals with
/// AppLocalizations.of(context).xxx calls, not an architectural change.
class AppStrings {
  const AppStrings._();
}
