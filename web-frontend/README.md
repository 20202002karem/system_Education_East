# M1 Web — نظام قسم الحاسوب (شرق غزة)

## تنبيه مهم قبل الاستخدام

هذه البيئة بلا اتصال شبكة (npm registry يرجع 403)، لذا **لم يُشغَّل** `npm install`
ولا `npm run build` ولا `npm test` فعليًا. كل الملفات مصدر React+TypeScript
حقيقي، لكنها بحاجة تثبيت وتشغيل فعليين لديك قبل الاعتماد عليها.

## التشغيل

```bash
npm install
cp .env.example .env    # اضبط VITE_API_BASE_URL على عنوان الـBackend
npm run dev              # تطوير
npm run build             # بناء إنتاج (يشغّل tsc أولاً)
npm test                  # Vitest — 9 ملفات اختبار
```

## البنية

```
src/
  lib/            apiClient.ts (fetch wrapper, {data}/{error} envelope, Idempotency-Key)
                  types.ts (Domain model مطابق لـ Batch 2 ERD)
  auth/           AuthContext (login/mfa/logout/me)، ProtectedRoute
  components/     Design System: Button, Input, Select, Card, Table, ConfirmDialog,
                  Snackbar, Pagination, Badge, states (Loading/Empty/Error)
  layout/         AppShell (تنقّل مبني على الدور)
  features/
    auth/         LoginPage, MfaVerifyPage
    users/        UsersListPage, UserDetailPage, UserFormDialog
    organization/ SitesListPage, SiteDetailPage, SiteFormDialog, AssignManagerDialog
    settings/     SettingsPage, ReferenceDataPage (4 قوائم في تبويبات)
    audit/        AuditLogPage, ChainChecksPage
tests/            Vitest + Testing Library — auth, mfa, authorization, users,
                  sites, settings/reference, audit, apiClient (9 ملفات)
```

## القرارات المتبعة

- **بدون مكتبة إدارة حالة إضافية**: React Context + useState/useReducer كافية لنطاق M1؛
  Redux/Zustand لم تُضف لتفادي تبعية غير ضرورية.
- **التوكن يُخزَّن في `sessionStorage`** (ليس `localStorage`) — يتوافق مع نموذج الجلسة
  في الـBackend (خمول 30 دقيقة / TTL مطلق) بدل بقائه إلى ما لا نهاية.
- **كل Endpoint مطابق حرفيًا** لمسارات/طرق Batch 3 §2 — راجع `lib/apiClient.ts` وملفات
  `*Api.ts` في كل feature.
- **صلاحيات الواجهة (ProtectedRoute/AppShell nav) طبقة UX فقط** — التفويض الفعلي من
  الـBackend دائمًا (انظر التعليق في `ProtectedRoute.tsx`).
