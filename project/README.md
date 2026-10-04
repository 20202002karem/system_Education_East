# M1 — نظام قسم الحاسوب (شرق غزة): Identity & Access, Organization, Settings & Reference Data, Audit & Integrity

## هذا الملف من إنتاج جلسة Claude، وليس مشروع Laravel قائمًا تم فحصه وتعديله

**تنبيه مهم قبل الاستخدام:** هذا الكود كُتب داخل بيئة sandbox بلا PHP/Composer وبلا
اتصال شبكة، وبالتالي **لم يُشغَّل ولم يُختبر فعليًا** — لا `composer install`،
لا `php artisan migrate`، لا `php artisan test`. كل الملفات مكتوبة يدويًا
اعتمادًا على SRS 1.0 وMVP Design 0.8.1 والملاحق ومصفوفة التتبع وBatch 1/2/3،
لكنها تحتاج مراجعة وتشغيلًا فعليين على جهازك قبل الاعتماد عليها.

## التشغيل

```bash
composer install
cp .env.example .env
php artisan key:generate
# اضبط DB_* في .env على قاعدة MySQL فعلية
php artisan migrate
php artisan db:seed          # ينشئ حساب رئيس قسم أولي (غيّر كلمة المرور فورًا)
php artisan serve
php artisan test             # 7 ملفات Feature tests
```

القيم الافتراضية لحساب رئيس القسم الأولي في `DatabaseSeeder`:
`BOOTSTRAP_CHAIRMAN_EMAIL` / `BOOTSTRAP_CHAIRMAN_PASSWORD` (ضعها في `.env`، لا تُبقِها
الافتراضية في أي بيئة حقيقية). لاحظ أن هذا الحساب يُنشأ بدون 2FA مفعّلة — و`AuthController`
يرفض تسجيل دخول رئيس القسم حتى يُفعَّل `mfa_enabled` ويُخزَّن `mfa_secret`؛ إتمام تفعيل
2FA لأول مرة (توليد السر وعرض QR) يحتاج Endpoint/أداة تزويد منفصلة لم يطلبها التصميم
المعتمد صراحة ضمن جداول Batch 2 — استُخدمت `TotpService::generateSecret()` و
`qrCodeUrl()` كوحدات جاهزة، لكن ربطها بمسار API مخصص للتزويد الأول متروك لك لإضافته
بأقل تغيير (لا يتعارض مع أي عقد معتمد في Batch 3).

## القرارات التي حسمها صاحب المنتج (ونُفذت هنا حرفيًا)

1. **login_identifier = email.** حقل `users.email`، فريد، تحقّق بصيغة بريد إلكتروني.
2. **users.mfa_secret** أُضيف (لم يكن موجودًا في Batch 2 الأصلية — Batch 3 §7/§9 وثّقت
   هذه كفجوة صريحة تمنع تنفيذ AUTH-07). مشفّر عبر Laravel `encrypted` cast
   (يعتمد على `APP_KEY`)، لا يظهر في أي استجابة API (`$hidden` على الموديل، ولا
   يُدرَج أبدًا في `AuditLogger::record()`).

## ما نُفِّذ (مطابقًا لعقود Batch 3، دون توسّع)

- **Auth:** `POST /auth/login`, `POST /auth/mfa/verify`, `POST /auth/logout`, `GET /auth/me`
- **Users:** كل Endpoints القسم 2.2 كاملة (CRUD، تعطيل/تفعيل، إعادة تعيين كلمة مرور،
  إنهاء الجلسات، منح/سحب صلاحيات فردية، site-scopes)
- **Sites & Site Managers:** القسم 2.3 كاملاً
- **Settings & Reference Data:** `settings` + `settings_history` + القوائم الأربع
  (device-categories, request-types, task-types, intake-channels)
- **Audit & Integrity:** `GET /audit-log` بالفلاتر، `GET /audit/chain-checks`،
  لا مسار كتابة/حذف على الإطلاق، سلسلة تجزئة SHA-256 (`AuditLogger`)، وأمر مجدول
  يومي `audit:verify-chain` (Batch 2 §B9)

كل مجموعة Endpoints غير Auth مقفلة على `role:chairman` حصرًا وفق مصفوفة الصلاحيات
في Batch 3 §4 — بما فيها قراءة `/sites` و`/audit-log`، لأن الوثائق تركتهما "⏳ يحتاج
قراراً" لبقية الأدوار ومبدأ "أقل صلاحية" يقتضي الإغلاق الافتراضي.

## ما لم يُنفَّذ عمدًا (خارج نطاق M1 بنص التعليمات)

لا Requests/Tasks (M3)، لا Assets (M2)، لا Operations/Approvals (M4)، لا تقارير (M5).
لا دور "مدير المديرية". لا Endpoint لاسترداد حساب رئيس القسم نفسه (AUTH-09 لا تزال
Pending — جهة الاسترداد غير محسومة). لا قيمة لـ`attachment_size_limit` (D-34 Pending).

## بنود لا تزال مفتوحة (منقولة من الوثائق كما هي، دون حسم من هذه الجلسة)

- AUTH-09: جهة استرداد حساب رئيس القسم
- D-34: قيمة حد حجم المرفقات
- P-07: هل يرى السكرتير/المهندس سجل التدقيق؟ (مغلق افتراضيًا الآن)
- هل يُسمح بأكثر من حساب "رئيس قسم" فعّال؟ (لا قيد حاليًا، كما في Batch 3 §9 بند 6)
- آلية تزويد TOTP الأولى (توليد السر + QR) لرئيس القسم — الخدمة جاهزة، لا Endpoint مخصص لها بعد
- آلية الإنذار عند كسر سلسلة التدقيق (بريد/إشعار) — الأمر المجدول يسجل `logger()->critical()` فقط الآن

## بنية المشروع

```
app/Console/Commands/VerifyAuditChain.php
app/Http/Controllers/Api/V1/*           # AuthController, UserController, ...
app/Http/Middleware/*                    # EnsureIdempotencyKey, TrackSessionActivity, RequireRole
app/Http/Requests/*                      # Form Request validation لكل Endpoint إنشاء/تعديل
app/Models/*                             # مطابقة لـ Batch 2 حرفيًا
app/Services/AuditLogger.php             # سلسلة التجزئة (append-only)
app/Services/IdempotencyService.php
app/Services/TotpService.php
app/Services/PasswordPolicy.php
app/Support/AccessPolicy.php             # AccessPolicy مركزية
app/Support/ApiResponse.php              # غلاف {data}/{error} الموحّد
database/migrations/*                    # 18 migration، جدول لكل جدول في Batch 2 + Sanctum
database/seeders/DatabaseSeeder.php
routes/api.php                           # مطابق حرفيًا لجدول Endpoints في Batch 3 §2
tests/Feature/*                          # 7 ملفات تغطي كل بنود القسم L من التعليمات
```
