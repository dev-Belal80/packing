# Frontend Plan — محطة التعبئة من البداية حتى تكوين البالتيه

هذا الملف هو خطة تنفيذية كاملة للـ Frontend بحيث يقدر **مدير المحطة** يختبر ويستخدم النظام من أول تسجيل الدخول إلى آخر خطوة في تكوين البالتيه، مع منع أكبر عدد ممكن من الأخطاء قبل وصولها للـ API.

الهدف هنا ليس مجرد “عرض شاشات”، بل بناء **تدفق state-aware** يعرف:

- إيه المسموح يتعمل في كل مرحلة.
- إمتى نمنع الإرسال قبل الطلب.
- إمتى نعرض تحذير بدل ما نكسر الواجهة.
- إزاي نحسب الكميات المتاحة والمستهلكة.
- إزاي نمنع اختيار خام انتهى أو أمر إنتاج غير صالح.

## 1) المستخدم المستهدف

- المستخدم: `station admin` أو `manager`
- الصلاحيات: كاملة داخل المحطة
- الاستخدام: تشغيل فعلي + اختبار كامل + حالات حدودية

## 2) السيناريو الكامل الذي يجب أن يدعمه الـ Frontend

التسلسل التشغيلي:

1. تسجيل الدخول.
2. تحميل بيانات الإعدادات الأساسية.
3. تسجيل استلام خام.
4. عرض الخام المتاح فقط.
5. إنشاء أمر تشغيل على خام متاح.
6. خصم الكمية المحجوزة من الخام في الواجهة بعد نجاح الحفظ.
7. منع إنشاء أمر تشغيل إذا الكمية المطلوبة أكبر من المتاح.
8. تسجيل الفرزة الناتجة من أمر التشغيل.
9. ترحيل الفرزة.
10. تكوين بالتيه من الفرزة المرحّلة فقط.
11. نقل البالتيه للتبريد ثم تأكيد الاستلام ثم الشحن.

## 3) الفكرة الأساسية: state machine للبيانات

الواجهة يجب أن تتعامل مع كل كيان كحالة واضحة، وليس مجرد فورم.

### خام الاستلام

الحالات المقترحة:

- `pending`
- `approved`
- `priced`
- `in_stock`
- `partially_used`
- `depleted`

### أمر التشغيل

الحالات المقترحة:

- `draft`
- `dispatched`
- `paused`
- `completed`
- `cancelled`

### الفرزة

الحالات المقترحة:

- `draft`
- `posted`
- `cancelled`

### البالتيه

الحالات المقترحة:

- `building`
- `built`
- `cooling`
- `cooled`
- `confirmed`
- `shipped`

## 4) قاعدة البيانات المنطقية في الواجهة

الـ Frontend لازم يحتفظ بنسخة cache من القوائم الأساسية، لكن ما يعتمدش عليها لو في بيانات أحدث من السيرفر.

### Entities أساسية

- `packhouses`
- `contacts`
- `raw-material-types`
- `production-lines`
- `production-stages`
- `pallet-types`
- `fridges`
- `raw-receipts`
- `production-orders`
- `sort-records`
- `pallets`

### Cache policy

- الكاش للـ lookups فقط.
- TTL مقترح: 24 ساعة.
- البيانات التشغيلية الحساسة مثل `raw-receipts`, `production-orders`, `pallets` لازم تتجدد بعد أي عملية حفظ ناجحة.

## 5) الصفحة الرئيسية للمحطة Dashboard

### المطلوب

لوحة واحدة توضح للمستخدم:

- خامات اليوم.
- الخام المتاح فعليًا.
- أوامر التشغيل المفتوحة.
- الفرزات غير المرحّلة.
- البالتيهات في التبريد أو الجاهزة.
- التنبيهات.

### قواعد العرض

- أي رقم مهم يظهر معه loading state.
- لو API فشل، استخدم cached summary إن وجد.
- لو لا يوجد cache، اعرض empty state واضح.

## 6) شاشة الإعدادات قبل التشغيل

قبل أي شغل، لازم الواجهة تتأكد من:

- API base URL.
- token أو session.
- packhouse الافتراضي.
- language/locale.

### منع الأخطاء

- لو لا توجد إعدادات، redirect إلى Settings.
- لا تطلق أي request قبل اكتمال settings bootstrap.
- أظهر سبب المشكلة بدل شاشة فارغة.

## 7) مسار استقبال الخام

### 7.1 شاشة raw receipt list

#### المطلوب في العرض

- رقم الاستلام.
- المورد.
- نوع الخام.
- الكمية الكلية.
- الكمية المستخدمة.
- الكمية المتاحة.
- الحالة.

#### قواعد مهمة

- لا تخلط بين `quantity_kg` و `available_qty`.
- `available_qty` هو الذي يحدد صلاحية الاستخدام في التشغيل.
- لو `available_qty = 0`، ضع الحالة `depleted` بصريًا.

### 7.2 شاشة create raw receipt

#### يجب أن تتحقق الواجهة من

- `packhouse_id` موجود.
- `contact_id` موجود لو المورد مطلوب.
- `raw_material_type_id` موجود.
- `quantity_kg > 0`.
- `transport_cost >= 0`.
- لو `gate_inquiry_id` أو `scale_note_id` مستخدمين، يكونوا تابعين لنفس المحطة.

#### ما لا يجب على الواجهة فعله

- لا تفترض `raw_material_type_id` من النص.
- لا تفترض `contact_id` من الاسم فقط.
- لا تسمح بحفظ خام ناقص الحقول الأساسية.

### 7.3 حالات الفشل في الاستلام

- contact غير موجود.
- raw material type غير موجود.
- quantity فارغة أو صفر.
- packhouse غير صحيح.
- تكرار reference أو payload غير صالح.

## 8) شاشة اختيار الخام لأمر التشغيل

هذه أهم نقطة في الـ frontend كله.

### Endpoint المطلوب

- `GET /api/production/raw-receipts/available`

### قواعد العرض

- اعرض فقط الخام الذي `available_qty > 0`.
- لا تعرض الخام المنتهي حتى لو ما زال موجودًا في database.
- اعرض مع كل خام:
  - `reference_no`
  - `contact`
  - `raw_material_type`
  - `quantity_kg`
  - `used_quantity`
  - `available_qty`
  - `receipt_date`

### فلترة إضافية

- `packhouse_id`
- `raw_material_type_id`
- search by reference or contact

### سلوك الواجهة

1. المستخدم يفتح شاشة إنشاء أمر التشغيل.
2. الواجهة تجيب الخام المتاح فقط.
3. المستخدم يختار خامًا من القائمة.
4. الفورم يحسب الكمية المتاحة قبل الإرسال.
5. إذا الكمية المطلوبة أكبر من المتاح، امنع submit.

## 9) شاشة إنشاء أمر التشغيل

### الحقول التي يجب أن تكون موجودة

- `packhouse_id`
- `raw_receipt_id`
- `product_id`
- `pallet_type_id`
- `production_line_id`
- `production_stage_id`
- `supervisor_id`
- `target_qty_kg`
- `actual_input_kg` إن وجد
- `order_date`
- `status`
- `notes`

### منطق مهم قبل الإرسال

- `target_qty_kg > 0`
- `target_qty_kg <= raw_receipt.available_qty`
- `production_line_id` تابع لنفس `packhouse_id`
- `supervisor_id` من نفس المحطة وصلاحياته مناسبة
- `product_id` و`pallet_type_id` مطلوبان

### بعد حفظ أمر التشغيل

الواجهة يجب أن:

- تحدث قائمة الخام المتاح مباشرة.
- تعيد تحميل `raw_receipts/available`.
- تعيد تحميل `production orders` list.
- تعرض رسالة نجاح فيها رقم الأمر.

## 10) حالات أمر التشغيل التي يجب منعها في الواجهة

### الحالة 1: كمية أكبر من المتاح

- لو الخام المتاح `1930` والـ user كتب `2500`.
- الواجهة تمنع الحفظ قبل الإرسال.

### الحالة 2: خام منتهي

- لو `available_qty = 0`، لا تسمح باختيار الخام أصلًا.

### الحالة 3: خط إنتاج خارج نفس المحطة

- لا تعرض lines ليست من نفس `packhouse_id`.

### الحالة 4: supervisor غير مناسب

- لا تعرض مستخدمين ليس لهم role مناسب للإنتاج.

## 11) شاشة الفرزة

### ما الذي تربطه الواجهة

- الفرزة يجب أن ترتبط بـ `production_order_id` أو بالبيانات الناتجة عنه.

### المتطلبات

- السطور يجب أن تكون على الأقل 1.
- كل سطر يجب أن يحتوي:
  - `raw_type`
  - `lot_no` اختياري
  - `production_line_id`
  - `production_order_id`
  - `grade_a_kg`
  - `grade_b_kg`
  - `grade_c_kg`
  - `waste_kg`
  - `returned_kg`

### قواعد المجموع

- مجموع السطر = A + B + C + waste + returned.
- `total_sort` يجب أن يكون أكبر من صفر.
- `waste_kg` لا يُسمح أن يكون سالبًا.
- لو waste تجاوز نسبة منطقية، أظهر warning.

### حالات الفشل

- لا يوجد lines.
- كل الأوزان صفر.
- `production_order_id` غير صحيح.
- `production_line_id` لا يطابق أمر التشغيل.

## 12) ترحيل الفرزة

### قبل الترحيل

- راجع totals.
- تأكد أن السطور كاملة.
- تأكد من عدم وجود سطر ناقص.

### بعد الترحيل

- غيّر حالة الفرزة إلى `posted`.
- اقفل التعديل والحذف في الواجهة.
- لو المستخدم حاول التعديل، اعرض سبب واضح.

## 13) شاشة البالتيه

### مصدر البيانات الصحيح

- `GET /api/export/pallets/available-orders`

### قاعدة مهمة

- البالتيه تُبنى من فرزة مرحّلة أو سطر متاح.
- لا تعتمد على إدخال يدوي لرقم الإنتاج إذا يمكن inference من السطر.

### الحقول الأساسية

- `available_order_id` أو `sort_record_line_id`
- `packhouse_id`
- `sort_record_id`
- `pallet_type_id`
- `product_id`
- `pallet_date`
- `cartons_count`
- `actual_weight`
- `net_weight`
- `status`

### التحقق قبل الإرسال

- `pallet_type_id` مطلوب.
- `pallet_date` مطلوب.
- `actual_weight > 0`.
- `net_weight >= 0`.
- `cartons_count >= 0`.
- لو اختار available order، لا يعيد user إدخال packhouse/product من الصفر إلا لو في override واضح.

### حالات الفشل

- no available orders.
- packing from draft sort record.
- pallet type غير موجود.
- weights غير منطقية.

## 14) التبريد والتأكيد والشحن

### Cooling

- عند بدء التبريد، اطلب `fridge_id`.
- `cooling_start` و`cooling_end` اختياريان حسب workflow.

### Confirm receipt

- لا تعرض الزر إلا إذا الحالة تسمح.

### Shipping

- لا تسمح بالشحن إلا إذا pallet `confirmed` أو جاهزة حسب قواعد المشروع.

## 15) خطة منع الأخطاء في الواجهة

### A) Form validation قبل الإرسال

كل form يجب أن يعمل validation محليًا قبل الـ submit.

أمثلة:

- numeric fields > 0
- dates valid
- required lookups موجودة
- array lengths صحيحة

### B) Server error mapping

لو رجع السيرفر `422`:

- اعرض رسائل الحقول كما هي.
- اربطها بالحقل الصحيح.

لو رجع `403`:

- افهم هل المشكلة permission أو state.
- اعرض banner واضح بدل alert عام.

لو رجع `404`:

- افحص إن الـ id صحيح.
- لو العنصر تم حذفه، اعمل refresh للقائمة.

لو رجع `419`:

- أعد login أو refresh token/CSRF حسب auth mode.

### C) Optimistic updates بحذر

- يمكن تحديث list بعد create/update/delete لكن فقط بعد success.
- لا تعمل optimistic reserve للخام إذا احتمال كبير يفشل الطلب.

### D) Debounce وthrottle

- search inputs لازم تكون debounced.
- لا تطلق API مع كل keypress.

### E) Race conditions

- لو المستخدم فتح شاشة raw receipts ثم حجز الخام من مكان آخر، أعد fetch قبل submit.
- لو الخام المتاح تغير، امنع submission وأعد تحميل البيانات.

## 16) API contract الذي يجب أن تلتزم به الواجهة

### قبل كل request

- تأكد أن token موجود.
- تأكد أن base URL صحيح.
- تأكد أن tenant context جاهز.

### بعد كل request

- لو success: حدّث cache / state.
- لو failure: لا تمسح بيانات المستخدم المدخلة.
- سجل `request_id` للـ support/debug.

## 17) حالات الاختبار الإلزامية

### Success cases

1. استلام خام جديد.
2. عرض الخام المتاح.
3. إنشاء أمر تشغيل من خام متاح.
4. حجز كمية جزئية.
5. استهلاك كامل الخام.
6. تسجيل فرزة.
7. ترحيل الفرزة.
8. إنشاء بالتيه.
9. تبريد البالتيه.
10. تأكيد الاستلام.

### Failure cases

1. خام غير متاح.
2. كمية تشغيل أكبر من المتاح.
3. خام لا ينتمي للمحطة.
4. production line غير صحيح.
5. supervisor غير صحيح.
6. فرزة بدون lines.
7. بالتيه من فرزة draft.
8. pallet type missing.
9. request بلا token.
10. permission denied.

### Boundary cases

1. كمية مساوية تمامًا للمتاح.
2. كمية أقل من المتاح بواحد فقط.
3. خام متبقّي 0.001.
4. waste عالي جدًا.
5. carton count = 0.
6. net weight أقل من صفر.

## 18) ترتيب التنفيذ داخل الـ Frontend

### المرحلة 1

- بناء `settings` bootstrap.
- بناء api client واحد.
- بناء lookup cache.

### المرحلة 2

- شاشة raw receipts.
- شاشة available raw receipts selector.

### المرحلة 3

- شاشة production orders مع خصم الكمية.
- local guards للكمية المتاحة.

### المرحلة 4

- شاشة sort records.
- post workflow.

### المرحلة 5

- شاشة pallets.
- cooling / confirm / ship.

### المرحلة 6

- error mapping + request_id.
- UX polish + empty states + loading states.

## 19) ملفات مقترحة للـ Frontend

- `src/settings/settingsStore.ts`
- `src/settings/SettingsGate.tsx`
- `src/lib/apiClient.ts`
- `src/lookups/useLookups.ts`
- `src/lookups/lookupsStore.ts`
- `src/features/reception/RawReceiptForm.tsx`
- `src/features/production/AvailableRawReceiptPicker.tsx`
- `src/features/production/ProductionOrderForm.tsx`
- `src/features/production/SortRecordForm.tsx`
- `src/features/export/PalletForm.tsx`
- `src/features/export/PalletCoolingPanel.tsx`

## 20) Definition of Done

- الواجهة تمنع الطلبات قبل تسجيل الدخول/الـ settings.
- لا يمكن اختيار خام منتهي في أمر التشغيل.
- لا يمكن عمل order أكبر من المتاح.
- الفرزة لا تُرحّل إلا إذا كانت صحيحة.
- البالتيه لا تُنشأ إلا من بيانات صالحة.
- أي خطأ من السيرفر يظهر بشكل مفهوم.
- الـ UI يعمل حتى مع فشل جزئي في الشبكة باستخدام cache حيثما أمكن.
