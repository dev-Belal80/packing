# Plan — React Settings Page + Settings APIs Bootstrap

الهدف: الـ Frontend (React) ما يبدأش أي شغل/requests قبل ما المستخدم يظبط Settings الأساسية، وبعدها يعمل Bootstrap للـ lookups من APIs (`/api/settings/*`).

---

## 1) تعريف الـ Settings Contract (Client-side)

**المطلوب تخزينه في `localStorage` تحت key واحد**: `packing.settings.v1`

حقول مقترحة:
- `apiMode`: `mock | real` (default: `real` لو السيرفر شغال، وإلا `mock`)
- `apiBaseUrl`: required لو `apiMode=real` (مثال: `https://your-domain.com`)
- `authToken`: Bearer token (اختياري، بس غالبًا مطلوب لتشغيل الـ protected routes)
- `branch`: قيمة فرع (string) — لازم
- `packhouseId`: ID — لازم
- `locale`: `ar | en` (default `ar`)

**Rules**
- لو `apiMode=real` لازم `apiBaseUrl` valid URL.
- لازم `branch` و `packhouseId`.

**Acceptance**
- لو settings ناقصة: المستخدم يتحول تلقائيًا إلى صفحة Settings.

---

## 2) Settings Page (UI) قبل بدء التطبيق

Route: `/settings`

UI minimal:
- Inputs: `apiMode`, `apiBaseUrl`, `authToken`, `branch`, `packhouseId`, `locale`
- Buttons: `Save`, `Reset`
- Inline validation errors

تدفق المستخدم:
1) يفتح app → يلاقي Settings لو ناقصة
2) يضغط Save → يرجع للصفحة الرئيسية

**Acceptance**
- مع `localStorage` فاضي: `/` تعمل redirect إلى `/settings`.

---

## 3) Settings Gate (Route Guard)

اعمل wrapper component `SettingsGate`:
- لو `isSettingsReady()` = false → redirect `/settings`
- غير كده → render children routes

**Acceptance**
- أي route محمي غير `/settings` ما يفتحش قبل settings.

---

## 4) API Client واحد + منع أي Network قبل Settings

اعمل `apiClient` واحد يستخدمه كل المشروع.

Rules:
- قبل أي request:
  - لو settings مش ready → throw `SettingsNotReadyError` (ولا تبعت request)
  - لو `apiMode=mock` → رجّع mock data (بدون network)
  - لو `apiMode=real` → استخدم `apiBaseUrl` + headers:
    - `Authorization: Bearer <authToken>` (لو موجود)
    - `Accept: application/json`

**Acceptance**
- مفيش requests بتطلع من المتصفح قبل Save للـ settings.

---

## 5) Bootstrap Lookups (Settings Resources)

بعد Save أو بعد فتح app وsettings جاهزة:

### Endpoints
(كلها `GET` وبتحتاج Bearer token + permission)
- `/api/settings/branches`
- `/api/settings/packhouses?search=`
- `/api/settings/products?search=`
- `/api/settings/production-lines?packhouse_id=&search=`
- `/api/settings/production-stages?search=`
- `/api/settings/pallet-types`
- `/api/settings/fridges?search=`
- `/api/settings/contacts?role=supplier|client&search=`
- `/api/settings/production-supervisors?search=` (permission = `production-order`)

### خطة تحميل البيانات
- أول ما settings تبقى جاهزة:
  1) نجيب `packhouses`
  2) نجيب `branches`
  3) نجيب `pallet-types`, `production-stages`
  4) لما المستخدم يختار packhouse: نجيب `production-lines` و`fridges` بـ `packhouse_id`

### Caching
- خزن lookups في `localStorage`:
  - key: `packing.lookups.v1`
  - مع `updatedAt` و TTL (مثلاً 24 ساعة)
- لو API فشل:
  - استخدم cached lookups لو موجودة
  - وإلا switch إلى `mock` mode أو اعرض رسالة واضحة

**Acceptance**
- Dropdowns الأساسية تشتغل حتى لو الـ API وقع (باستخدام cache أو mock).

---

## 6) Error Handling في الفرونت (Minimum)

- اعرض banner صغير عند فشل تحميل lookups:
  - `Failed to load settings data. Using cached data.`
- سجّل `request_id` لو رجع من السيرفر.

**Acceptance**
- أي error واضح للمستخدم ومش بيكسر UI.

---

## 7) Deliverables (Suggested Files)

- `src/settings/settingsTypes.ts`
- `src/settings/settingsStore.ts`
- `src/settings/SettingsPage.tsx`
- `src/settings/SettingsGate.tsx`
- `src/lib/apiClient.ts`
- `src/lookups/lookupsStore.ts` (cache + TTL)
- `src/lookups/useLookups.ts`
- `src/mock/mockApi.ts` + `src/mock/fixtures.ts`

---

## 8) Definition of Done (Checklist)

- [ ] `/settings` موجودة ومش بتحتاج API
- [ ] SettingsGate شغال والـ routes محمية
- [ ] `apiClient` يمنع requests قبل settings
- [ ] Lookups بتتحمل وتتكاش
- [ ] عند فشل API: cache أو mock بدل ما التطبيق يقع
