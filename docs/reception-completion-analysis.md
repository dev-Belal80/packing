# تحليل إنجاز مرحلة الاستقبال

## ملخص تنفيذي
تم تنفيذ معظم متطلبات مرحلة الاستقبال على مستوى الـ Backend و الـ Frontend مع وجود فجوات محددة مرتبطة بـ Raw Receipt workflow والباركود وبعض واجهات المدير. المرحلة التالية هي تفعيل دور مشرف الانتاج وربطها بمخرجات الاستقبال مع الحفاظ على قواعد العمل الحالية.

## ما تم إنجازه (Backend + Frontend)

### Backend المنجز لموظف الاستقبال

**Migrations:**
- `raw_delivery_orders` — أوامر توريد الخام (كل الحقول + auto calculations)
- `gate_inquiries` — استعلامات البوابة
- `scale_notes` — علامات الوزن مع حسابات تلقائية
- `transport_cost_distributions` — توزيع تكلفة النقل

**Models:**
- `RawDeliveryOrder` — مع `calculate()` static method للحسابات التلقائية
- `GateInquiry`
- `ScaleNote` — مع auto calc في boot()
- `TransportCostDistribution`

**Controllers:**
- `RawDeliveryOrderController` — CRUD + confirm
- `GateInquiryController` — CRUD
- `ScaleNoteController` — CRUD + auto calc على update
- `TransportCostController` — getReceipts + distribute

**Reports:**
- `RawReceiptsExport`, `GateInquiriesExport`, `ScaleNotesExport`, `DeliveryOrdersExport`
- `ReceptionDailyExport` — 4 sheets في ملف واحد
- `ReceptionReportsController` — PDF + Excel لكل نوع
- Blade views للـ PDF

### Frontend المنجز لموظف الاستقبال

**Screens:**
- `RawDeliveryOrderForm` — 7 sections مع auto-calculations بـ useEffect
- `DeliveryOrdersList` — جدول + side panel + confirm/delete
- `GateInquiryForm` — form كامل
- `GateInquiriesList` — جدول مع فلترة
- `ScaleNoteForm` — form مع حسابات أوزان تلقائية
- `TransportCostDistribution` — خطوتين: تحميل استلامات + توزيع
- `ReceptionReports` — PDF/Excel لكل نوع + daily full report

**Hooks:**
- `useRawDeliveryOrderForm` — كل الحسابات التلقائية
- `useDeliveryOrders` — React Query hooks

**API Modules:**
- `deliveryOrdersApi` — 6 endpoints
- `receptionReportsApi` — 9 endpoints (PDF + Excel)

**Infra:**
- `GroupedNavbar` — dropdown navigation
- `Sidebar` — collapsible groups
- `RBAC` — permissions per screen
- Responsive layout

## الفجوات المتبقية مع الأولوية

### User Stories غير مكتملة

| الـ Story | الوضع | المطلوب |
|-----------|-------|---------|
| US-RC-003: استقبال وفحص الخام (Raw Receipt) | ⚠️ جزئي | الـ form موجود بس مش مربوط بـ GateInquiry و ScaleNote بشكل wizard |
| US-RC-004: طباعة وصل الاستلام بباركود | ❌ ناقص | Barcode generation + print template |
| US-MG-002: تسعير الخام (Manager only) | ❌ ناقص | شاشة تسعير + bulk pricing |
| US-MG-005: توزيع تكلفة النقل | ✅ مكتمل | — |
| BR-005: Barcode/QR على كل مستند | ❌ ناقص | `picqer/php-barcode-generator` integration |

### Technical Gaps
- `raw_receipts` table موجودة في الـ migrations القديمة بس الـ form مش كامل
- الـ `RawReceiptForm` بيستخدم wizard بس Step 1 -> Step 2 -> Step 3 مش متصلين صح
- مفيش `ContactSearchInput` component (بتستخدم input عادي بدله)
- الـ `scale_note_id` و `gate_inquiry_id` مش بيتربطوا بالـ raw receipt تلقائيا

## خطة الإنتاج (المرحلة الجاية)

### مشرف الانتاج — المرحلة الجاية بالكامل

**الشاشات المطلوبة:**
- `ProductionOrderForm` (امر التشغيل)
- `ProductionOrdersList` (قائمة الاوامر)
- `SortRecordForm` (تسجيل الفرزة)
- `AttendanceForm` (حضور العمالة)
-   انشاء صرف امر تشفيل 
-   انشاء تشغيل للغير
- `AttendanceList` (قائمة الحضور المهندسين فقط)
- `ProductionReports` (تقارير الانتاج)

### امر تشغيل — الخانات المطلوبة (وفق الجداول الحالية)

**الخانات الاساسية في النموذج:**
- الفترة المحاسبية
- رقم التشغيلة
- الفرع
- التاريخ
- محطة التعبئة
- الصنف المستهدف
- مرحلة الانتاج
- الحوشة / المورد
- العبوة
- العميل
- الكمية المتوقعة
- خط الانتاج
- الخام المتبقي
- المشرف
- البيان
- مشرفون اخرون
- كمية الفرزة
- كمية الهالك
- سعر الكيلو
- الخام المعبأ
- كود خاص

**مطابقة سريعة مع جداول قاعدة البيانات:**
- `production_orders`: `reference_no`, `packhouse_id`, `raw_receipt_id`, `product_id`, `production_line_id`, `production_stage_id`, `supervisor_id`, `target_qty_kg`, `actual_input_kg`, `order_type`, `client_contact_id`, `status`, `started_at`, `completed_at`
- `raw_receipts`: مصدر بيانات الخام المتبقي (حسب `status = 'in_stock'`)
- `sort_records`: `grade_a_kg`, `grade_b_kg`, `grade_c_kg`, `normal_waste_kg`, `damaged_kg`

**ملاحظات تنفيذية:**
- حقول مثل الفترة المحاسبية/الفرع/البيان/الكود الخاص غير موجودة حاليا في جدول `production_orders` وتحتاج اضافة في migration اذا كانت مطلوبة في الحفظ.
- حقول مثل كمية الفرزة/الهالك تتبع `sort_records` وليس `production_orders`.

## Dependencies Map

```
الاستقبال <- الانتاج:

raw_receipts (status = 'in_stock')
    ↓
production_orders (raw_receipt_id)
    ↓ [dispatch]
production_order_pickings (stock deduction)
    ↓
sort_records (production results)
    ↓
pallets (export module)
```

مشرف الانتاج محتاج:
1. خام مسعر في المخزون (`raw_receipts.status = 'in_stock'`)
2. `packhouse_id` موجود ومفعل
3. `production_line_id` موجود في الاعدادات
4. `product_id` موجود في الاعدادات
5. `production_stage_id` موجود في الاعدادات

## Business Rules Status

| الـ Rule | مطبق Backend | مطبق Frontend |
|----------|--------------|---------------|
| BR-001: Multi-tenant isolation | ✅ | ✅ |
| BR-002: Contact مرن (مورد/عميل) | ✅ | ⚠️ جزئي |
| BR-003: خام مش بيدخل المخزون قبل التسعير | ✅ | ❌ ناقص UI |
| BR-004: التسلسل الالزامي | ✅ backend validation | ❌ frontend لا يمنع |
| BR-005: Barcode/QR | ❌ | ❌ |
| BR-006: Approval workflow | ⚠️ جزئي | ⚠️ جزئي |
| BR-007: Read-only عند انتهاء الاشتراك | ✅ middleware | ✅ |

## قائمة المهام المقترحة للـ Sprint القادم

**اسبوع 1 — اكمال ثغرات الاستقبال:**
- [ ] `ContactSearchInput` component مشترك (searchable dropdown يجيب contacts من API)
- [ ] اكمال `RawReceiptForm` wizard (4 steps متصلين فعلا)
- [ ] ربط `gate_inquiry_id` و `scale_note_id` بالاستلامة تلقائيا
- [ ] شاشة تسعير الخام للمدير

**اسبوع 2 — مرحلة الانتاج:**
- [ ] Settings: اعدادات خطوط الانتاج + المراحل + المنتجات
- [ ] `ProductionOrderForm` — مع FIFO stock selector
- [ ] `ProductionOrdersList` — مع status management
- [ ] `SortRecordForm` — مع balance validation
- [ ] `AttendanceForm` + `AttendanceList`

**اسبوع 3 — تقارير الانتاج:**
- [ ] Production stats API endpoint
- [ ] `ProductionReports` screen (PDF + Excel)
- [ ] Dashboard KPIs من بيانات حقيقية

## ملاحظات للـ Developer

```
1. قبل ما تبدا الانتاج، تاكد ان في خام مسعر في المخزون
   -> php artisan tinker
   -> RawReceipt::where('status', 'in_stock')->count()
   -> لو 0، اعمل seed او سعر استلامة موجودة

2. الـ production_lines و products لازم تتحط في الاعدادات اول
   -> Settings screen -> خطوط الانتاج
   -> Settings screen -> المنتجات

3. الـ FIFO في امر التشغيل:
   -> يعرض الخام الاقدم اولا (order by created_at ASC)
   -> بيتحقق من availableQty() قبل الصرف

4. balance check في الفرزة:
   -> (A + B + C + waste + damaged) = actual_input_kg +- 0.5%
   -> لو الفرق اكبر -> error قبل الحفظ
```
