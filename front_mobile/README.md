# M1 Mobile — نظام قسم الحاسوب (شرق غزة)

## تنبيه مهم قبل الاستخدام

**لا يوجد Flutter/Dart SDK إطلاقًا في البيئة التي كُتب فيها هذا الكود.** لم يتم:
- تشغيل `flutter pub get`
- تشغيل `flutter analyze`
- تشغيل `flutter test`
- بناء أو تشغيل التطبيق على أي جهاز/محاكي

كل الملفات مصدر (source) مكتوب يدويًا اعتمادًا على SRS 1.0 وMVP Design 0.8.1
وBatch 1/2/3، وعلى نفس الـdomain model المستخدم في الـWeb frontend. **راجعه
وشغّله فعليًا قبل الاعتماد عليه.**

## التشغيل

```bash
flutter pub get
flutter analyze
flutter test
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api   # Android emulator
# أو: --dart-define=API_BASE_URL=http://localhost:8000/api         # iOS simulator / سطح المكتب
```

## البنية (Clean Architecture + Cubit، Feature-first)

```
lib/
  core/
    network/       ApiClient (Dio) + ApiException (Batch 3 §1 error envelope)
    storage/        SecureTokenStorage (Keychain/Keystore — flutter_secure_storage)
    theme/          AppTheme, AppColors, AppSpacing (مطابقة لـ tokens.css في الويب)
    widgets/        AppButton, AppTextField, AppStates, ConfirmDialog, AppSnackBar
    routing/        AppRouter, AuthGate, LandingPage (M1 فقط — بدون Dashboard M2)
    di/             injection_container.dart (get_it)
    l10n/           app_strings.dart (seam جاهز للإنجليزية لاحقًا — راجع التعليق فيه لصدق الفجوة)
  features/
    auth/           data / domain / repositories/entities / presentation (Cubit + pages)
    users/
    organization/
    settings/
    audit/
test/
  features/auth/    AuthCubit, AuthRepositoryImpl
  features/users/   UsersListCubit, UserDetailCubit, UserFormPage (validation)
  features/organization/  SitesListCubit
  core/network/     ApiException
  core/routing/     AuthGate (authorization UI)
```

## القرارات المتبعة

- **Cubit فقط** (لا Bloc كامل، لا GetX) — flutter_bloc.
- **Repository pattern صريح**: كل Cubit يعتمد على واجهة Domain (`abstract class XRepository`)
  فقط، أبدًا على `*RemoteDataSource` أو `Dio` مباشرة.
- **User entity واحد مشترك** بين auth و users (`features/users/domain/entities/user.dart`)
  — لا يوجد مفهومان مختلفان للمستخدم (تعليمات §9).
- **لا يوجد حقل `mfa_secret` في أي مكان** في الكود — لا في الـentities، لا في الـmodels، لا في السجلات.
- **توكن الدخول** يُخزَّن حصرًا عبر `flutter_secure_storage` (Keychain/Keystore)، لا SharedPreferences.
- **Navigation M1 فقط**: `LandingPage` شبكة تنقل بسيطة (Users/Sites/Settings/Reference/Audit)
  بدون أي Dashboard أو إحصاءات من M2.

## فجوات معروفة (لم تُحسم هنا، منقولة كما هي)

- **الإنجليزية لاحقًا**: البنية جاهزة (`core/l10n/app_strings.dart` يشرح لماذا لم تُستخرج
  النصوص كاملة الآن ولماذا هذا قرار واعٍ لا إهمال).
- **تزويد 2FA الأول لرئيس القسم** (توليد QR): لا شاشة له هنا أيضًا، لنفس السبب المذكور
  في تقرير الـBackend — لا Endpoint معتمد له في Batch 3.
- لا اختبارات integration/E2E فعلية (تحتاج جهاز/محاكي حقيقي لم يتوفر في هذه البيئة).
