# Flow المحطة من البداية للنهاية (UI) — استقبال خام → إنتاج → فرزة → تكوين بالتيه

الملف ده بيشرح الـ **flow العملي داخل واجهة المستخدم (UI)** لموظف محطة عنده **كل الصلاحيات**: من أول استقبال الخام لحد تكوين البالتيه، مع مثال بيانات واقعي في كل مرحلة.

---

## افتراضات قبل البداية

- الموظف مسجّل دخول (Station Admin / Manager) وعنده كل الصلاحيات.
- الإعدادات الأساسية موجودة (محطة تعبئة، خطوط إنتاج، مراحل، أصناف، عملاء/موردين، أنواع بالتيه، ثلاجات).
- هنمشي على مثال واحد ثابت علشان تربط كل المراحل ببعض.

---

## مثال بيانات ثابت هنستخدمه في كل المراحل (Scenario)

استخدم القيم دي كـ “سيناريو اليوم”:

- التاريخ: `2026-05-20`
- الفرع: `الرئيسي`
- محطة التعبئة (Packhouse): `محطة 1`
- المورد (Supplier): `شركة المورد`
- العميل (Client / Importer): `شركة العميل`
- الصنف (Product): `برتقال`
- خط الإنتاج (Production Line): `Line 3`
- مرحلة الإنتاج (Production Stage): `Stage 1`
- المشرف (Supervisor): `Mohamed Supervisor`
- نوع البالتيه (Pallet Type): `EU`
- الثلاجة (Fridge): `Fridge A`

بيانات السيارة:
- رقم السيارة: `EGY-1234`
- السائق: `Ahmed`

الكميات:
- كمية خام داخلة: `1200 kg`
- Target للإنتاج: `1000 kg`

---

## 0) تسجيل الدخول (UI)

### الخطوات

1) افتح شاشة **Login**.
2) اكتب الإيميل/الباسورد.
3) بعد الدخول، افتح **Profile / حسابي** وتأكد إن الدور: `manager` أو `station_admin`.

### مثال إدخال

- Email: `station.admin@demo.local`
- Password: `password`

### المتوقع

- تقدر تفتح شاشات: الاستقبال + الإنتاج + الفرزة + البالتيه + الإعدادات.

---

## 1) تجهيز الإعدادات (Settings) — خطوة سريعة للتأكد

قبل ما تبدأ شغل، راجع من **Settings** إن القوائم دي متاحة (لأن كل النماذج بتعتمد عليها):

- Packhouses
- Products
- Production Lines
- Production Stages
- Production Supervisors
- Contacts
- Pallet Types
- Fridges

### مثال (إيه تختار)

في أي نموذج لاحق، لما تلاقي dropdown:
- اختار **Packhouse = محطة 1**
- اختار **Product = برتقال**
- اختار **Line = Line 3**

---

## 2) استقبال الخام (Reception)

عندك طريقين في UI:

- **طريق سريع (الأبسط):** Raw Receipt مباشرة
- **طريق كامل:** Delivery Order → Gate Inquiry → Scale Note → Raw Receipt

> لو مش فارق معاك تتبع البوابة/الميزان، ابدأ بالطريق السريع.

---

### 2.A) الطريق السريع — Raw Receipt مباشرة

#### الخطوات

1) ادخل شاشة **Reception → Raw Receipts (استلامة خام)**.
2) اضغط **Create / إضافة استلامة**.
3) املا الحقول الأساسية.
4) اضغط **Save**.
5) لو عندك زر **Approve**: اضغطه (حسب سياسة التشغيل).

#### مثال إدخال (Raw Receipt)

البيانات الأساسية:
- Packhouse: `محطة 1`
- Receipt date: `2026-05-20`
- Branch: `الرئيسي`

المورد:
- Contact: `شركة المورد`
- Contact role: `supplier`

السيارة:
- Vehicle number: `EGY-1234`
- Driver name: `Ahmed`

الكمية والسعر:
- Quantity (kg): `1200`
- Price per kg: `10.50`
- Total price: `12600` (لو الشاشة بتحسبه سيبها)

مصروفات:
- Transport cost: `500`

الحالة:
- Status: `in_stock` (أو الافتراضي)

#### المتوقع بعد الحفظ

- الاستلامة تظهر في قائمة Raw Receipts.
- بيتولد رقم مرجعي/ID.
- لو عملت Approve: الحالة تتحول `approved`.

---

### 2.B) الطريق الكامل — (Delivery Order → Gate Inquiry → Scale Note → Raw Receipt)

#### 2.B.1) Delivery Order (أمر توريد خام)

1) ادخل شاشة **Reception → Delivery Orders**.
2) اضغط **Create**.

مثال إدخال:
- Packhouse: `محطة 1`
- Order date: `2026-05-20`
- Branch: `الرئيسي`
- Raw type: `برتقال`
- Received qty: `1200`
- Supplier: `شركة المورد`
- Client: `شركة العميل`

3) **Save**.
4) لو فيه زر **Confirm**: اضغطه علشان يبقى confirmed.

#### 2.B.2) Gate Inquiry (استعلام بوابة)

1) ادخل شاشة **Reception → Gate Inquiry**.
2) اضغط **Create**.

مثال إدخال:
- Packhouse: `محطة 1`
- Entry date: `2026-05-20`
- Entry time: `08:15`
- Contact (supplier): `شركة المورد`
- Raw type: `برتقال`
- Vehicle number: `EGY-1234`
- Driver name: `Ahmed`
- Expected qty: `1200`
- Delivery order: اختار أمر التوريد اللي لسه عملته
- Inquiry status: `received`

3) **Save**.

#### 2.B.3) Scale Note (علم وزن)

1) ادخل شاشة **Reception → Scale Notes**.
2) اضغط **Create**.

مثال إدخال:
- Packhouse: `محطة 1`
- Note date: `2026-05-20`
- Note time: `08:30`
- Contact: `شركة المورد`
- Raw type: `برتقال`
- Vehicle number: `EGY-1234`
- Gross weight: `1500`
- Tare weight: `300`
- Gate inquiry: اختار الاستعلام اللي لسه عملته

3) **Save**.

#### 2.B.4) Raw Receipt وربطه بالبوابة/الميزان

1) ادخل شاشة **Reception → Raw Receipts**.
2) **Create**.

مثال إدخال:
- Packhouse: `محطة 1`
- Receipt date: `2026-05-20`
- Branch: `الرئيسي`
- Contact: `شركة المورد`
- Vehicle number: `EGY-1234`
- Quantity (kg): `1200`
- Price per kg: `10.50`
- Transport cost: `500`
- Gate inquiry: اختار Gate Inquiry اللي عملته
- Scale note: اختار Scale Note اللي عملته

3) **Save**.
4) (اختياري) **Approve**.

---

## 3) أمر إنتاج (Production Order)

### الخطوات

1) ادخل شاشة **Production → Orders (أوامر الإنتاج)**.
2) اضغط **Create**.
3) اختار الاستلامة الخام + الصنف + خط الإنتاج… إلخ.
4) **Save**.
5) اضغط **Dispatch** لبدء التنفيذ.

### مثال إدخال

- Packhouse: `محطة 1`
- Raw receipt: اختار الاستلامة اللي عملتها (من الخطوة 2)
- Product: `برتقال`
- Pallet type: `EU`

- Production line: `Line 3`
- Production stage: `Stage 1`
- Supervisor: `Mohamed Supervisor`

- Branch: `الرئيسي`
- Order date: `2026-05-20`
- Target qty (kg): `1000`

- Supplier: `شركة المورد` (لو موجود)
- Client: `شركة العميل` (لو موجود)

- Status: `draft` (أو الافتراضي)
- Notes: `تشغيل اليوم`

### المتوقع

- بعد Save: الأمر يظهر `draft`.
- بعد Dispatch: الحالة تتحول `dispatched`.

---

## 4) تسجيل الفرزة (Sort Record) ثم ترحيلها

### الخطوات

1) ادخل شاشة **Production → Sort Records (تسجيل الفرزة)**.
2) اضغط **Create**.
3) املا Header.
4) أضف Lines (صنف/لوط/أوزان…).
5) **Save**.
6) اضغط **Post / ترحيل**.

### مثال إدخال (Header)

- Packhouse: `محطة 1`
- Sort date: `2026-05-20`
- Sort time: `12:30`
- Branch: `الرئيسي`
- Description: `فرزة اليوم`

### مثال Line واحدة

- Raw type: `برتقال`
- Lot no: `LOT-0055`
- Production line: `Line 3`
- Production order: اختار أمر الإنتاج اللي عملته

الأوزان (kg):
- Grade A: `700`
- Grade B: `200`
- Grade C: `50`
- Waste: `30`
- Returned: `20`

### المتوقع

- الـ UI يحسب Totals ويعرض `Total sort = 1000`.
- بعد Save: `draft`.
- بعد Post: `posted` + التعديل/الحذف يتقفّلوا.

---

## 5) تكوين البالتيه (Pallet Builder)

### الخطوات

1) ادخل شاشة **Export → Pallet Builder** (أو Export → Pallets حسب UI).
2) افتح جدول **Available Orders**.
3) اختار سطر مرتبط بالفرزة المرحّلة.
4) يظهر **Create Pallet modal**.
5) املا بيانات الكراتين/الأوزان ثم **Save**.

### مثال اختيار من Available Orders

- فعل `Lot only` لو عايز اللي عنده Lot.
- دور على `LOT-0055`.
- اختار السطر.

### مثال إدخال (Create Pallet)

Header:
- Pallet date: `2026-05-20`
- Branch: `الرئيسي`
- Wooden pallet no: `WP-12`
- Lot no: `LOT-0055`

Product details:
- Grade: `A`

Cartons & weights:
- Cartons count: `30`
- Actual weight: `600`
- Net weight: `594.5`

Status:
- Status: `building`

### المتوقع

- البالتيه تظهر في قائمة Pallets برقم مرجعي (مثل DS…)
- القيم الأساسية ظاهرة: grade / cartons / weights / status.

---

## 6) تعديل بالتيه (Edit Pallet)

### الخطوات

1) من قائمة **Pallets** افتح البالتيه اللي لسه عملتها.
2) اضغط **Edit**.
3) تأكد إن كل الحقول متعبّية بقيمها القديمة.
4) عدّل أي قيمة ثم **Save**.

### مثال تعديل

- Storage location: `A-1`
- Cooling end (لو موجود في شاشة edit): `2026-05-20 18:00`

---

## 7) (اختياري) التبريد + تأكيد الاستلام

### 7.1) Cooling

1) افتح البالتيه.
2) اضغط **Start/Update Cooling**.
3) اختار:
   - Fridge: `Fridge A`
   - Cooling start: `2026-05-20 14:00`
   - Cooling end: `2026-05-20 18:00`
   - Entry temp: `6.5`
4) Save.

### 7.2) Confirm Receipt

- اضغط **Confirm receipt**.

المتوقع:
- Receipt confirmed = Yes
- Status غالبًا يتحول `confirmed`.

---

## مشاكل شائعة في الـ UI (وحلها)

### Available Orders فاضية

لو جدول Available Orders فاضي:

1) اتأكد إن الفرزة اتعمل لها **Post**.
2) اتأكد إن الفرزة فيها Lines وإجمالي الفرزة أكبر من 0.
3) لو عامل فلتر `Lot only`، اتأكد إن `lot_no` مكتوب في الـ line.

### زر Update/Delete مش شغال

- لو البالتيه `shipped` (أو `is_shipped = true`) التعديل/الحذف بيترفض.

---

## ملخص سريع (UI)

1) Login
2) Settings (تأكيد البيانات الأساسية)
3) Reception: Raw Receipt (أو Delivery Order → Gate Inquiry → Scale Note → Raw Receipt)
4) Production: Production Order → Dispatch
5) Production: Sort Record → Post
6) Export: Pallet Builder → Create Pallet → (Edit / Cooling / Confirm)
