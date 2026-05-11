<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('packhouse_id')->constrained();

            // ─── Header ───────────────────────────────────────
            $table->string('reference_no')->unique();        // رقم الأذن (auto: XXXX/2026)
            $table->year('year');                            // السنة
            $table->string('branch')->nullable();            // الفرع
            $table->date('order_date');                      // التاريخ
            $table->string('agricultural_season')->nullable(); // الموسم الزراعي
            $table->string('work_order')->nullable();          // أمر العمل
            $table->string('cost_center')->nullable();         // مركز التكلفة
            $table->string('raw_type')->nullable();            // الصنف

            // ─── Locations ────────────────────────────────────
            $table->string('loading_warehouse')->nullable();    // مخزن التحميل
            $table->string('destination_warehouse')->nullable(); // إلى مخزن
            $table->string('supplying_station')->nullable();    // المحطة الموردة
            $table->string('delivery_station')->nullable();     // محطة التسليم
            $table->string('loading_warehouse_season')->nullable();  // موسم مخزن التحميل
            $table->string('supply_warehouse_season')->nullable();   // موسم مخزن التوريد

            // ─── Description ──────────────────────────────────
            $table->text('description_ar')->nullable();    // البيان (عربي)
            $table->text('description_en')->nullable();    // البيان (إنجليزي)
            $table->string('reference_number')->nullable(); // الرقم المرجعي

            // ─── Weight ───────────────────────────────────────
            $table->decimal('weight_on_entry', 10, 3)->default(0);  // الوزن عند الدخول
            $table->decimal('weight_on_exit', 10, 3)->default(0);   // الوزن عند الخروج
            $table->decimal('total_quantity', 10, 3)->default(0);   // الكمية الإجمالية (auto = entry - exit)

            // ─── Sale Section (بيانات البيع) ──────────────────
            $table->foreignId('client_contact_id')->nullable()->constrained('contacts'); // العميل
            $table->string('sale_order')->nullable();                // أمر البيع
            $table->decimal('received_qty', 10, 3)->default(0);     // الكمية المستلمة
            $table->decimal('discount_pct', 5, 4)->default(0);      // نسبة الخصم
            $table->decimal('discount_qty', 10, 3)->default(0);     // كمية الخصم (auto)
            $table->decimal('extra_discount_pct', 5, 4)->default(0); // نسبة خصم إضافي
            $table->decimal('extra_discount_qty', 10, 3)->default(0); // كمية خصم إضافي (auto)
            $table->string('invoice_number')->nullable();           // رقم الفاتورة
            $table->decimal('net_qty', 10, 3)->default(0);          // الكمية الصافية (auto)
            $table->decimal('price_per_unit', 10, 4)->default(0);   // السعر
            $table->decimal('total_amount', 10, 2)->default(0);     // الإجمالي (auto = net_qty * price)
            $table->integer('units_count')->default(0);             // عدد الوحدات
            $table->decimal('sorting_cost', 10, 2)->default(0);     // تكلفة الفرز
            $table->decimal('sorting_cost_per_ton', 10, 4)->default(0); // تكلفة الفرز بالطن (auto)
            $table->decimal('other_expenses', 10, 2)->default(0);   // مصروفات أخرى
            $table->decimal('supply_expenses', 10, 2)->default(0);  // مصروفات التوريد

            // ─── Supply Section (بيانات التوريد) ──────────────
            $table->foreignId('supplier_contact_id')->nullable()->constrained('contacts'); // لمورد
            $table->string('supply_order')->nullable();                  // أمر التوريد
            $table->string('supply_season')->nullable();                 // موسم التوريد
            $table->decimal('supply_qty', 10, 3)->default(0);           // كمية التوريد
            $table->decimal('supply_discount_pct', 5, 4)->default(0);   // نسبة خصم التوريد
            $table->decimal('supply_discount_qty', 10, 3)->default(0);  // خصم التوريد (auto)
            $table->decimal('net_supply_qty', 10, 3)->default(0);       // صافي كمية التوريد (auto)
            $table->decimal('supplied_qty', 10, 3)->default(0);         // الكمية الموردة
            $table->decimal('cost_price', 10, 4)->default(0);           // سعر التكلفة
            $table->decimal('total_cost', 10, 2)->default(0);           // إجمالي التكلفة (auto)
            $table->integer('supply_units_count')->default(0);          // عدد الوحدات

            // ─── Transport Section (بيانات النقل) ─────────────
            $table->string('transport_contractor')->nullable();         // مقاول النقل
            $table->integer('transport_units')->default(0);            // عدد وحدات النقل
            $table->decimal('transport_unit_cost', 10, 2)->default(0); // تكلفة وحدة النقل
            $table->decimal('transport_total', 10, 2)->default(0);     // تكلفة النقل (auto)
            $table->decimal('transport_discount_qty', 10, 3)->default(0); // كمية الخصم
            $table->decimal('transport_price', 10, 4)->default(0);     // السعر
            $table->decimal('transport_discount_value', 10, 2)->default(0); // قيمة الخصم (auto)

            // ─── Status ───────────────────────────────────────
            $table->string('status')->default('draft'); // draft|confirmed|cancelled
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'order_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_delivery_orders');
    }
};
