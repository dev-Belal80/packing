<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_delivery_orders', function (Blueprint $table): void {
            if (!Schema::hasColumn('raw_delivery_orders', 'packhouse_id')) {
                $table->foreignId('packhouse_id')->nullable()->after('tenant_id')->constrained();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'accounting_period')) {
                $table->string('accounting_period')->nullable()->after('reference_no');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'branch')) {
                $table->string('branch')->nullable()->after('accounting_period');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'order_date')) {
                $table->date('order_date')->nullable()->after('branch');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'order_time')) {
                $table->time('order_time')->nullable()->after('order_date');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'raw_type')) {
                $table->string('raw_type')->nullable()->after('raw_material_type_id');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'color')) {
                $table->string('color')->nullable()->after('raw_type');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'season')) {
                $table->string('season')->nullable()->after('color');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'delivery_location')) {
                $table->string('delivery_location')->nullable()->after('season');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'receipt_location')) {
                $table->string('receipt_location')->nullable()->after('delivery_location');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'delivery_province')) {
                $table->string('delivery_province')->nullable()->after('receipt_location');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'receipt_province')) {
                $table->string('receipt_province')->nullable()->after('delivery_province');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'vehicle_number')) {
                $table->string('vehicle_number')->nullable()->after('receipt_province');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'driver_name')) {
                $table->string('driver_name')->nullable()->after('vehicle_number');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'counter_on_entry')) {
                $table->decimal('counter_on_entry', 10, 2)->default(0)->after('driver_name');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'counter_on_exit')) {
                $table->decimal('counter_on_exit', 10, 2)->default(0)->after('counter_on_entry');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'price_per_unit')) {
                $table->decimal('price_per_unit', 10, 4)->default(0)->after('counter_on_exit');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'invoice_number')) {
                $table->string('invoice_number')->nullable()->after('price_per_unit');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'ticket_no')) {
                $table->string('ticket_no')->nullable()->after('invoice_number');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'rate')) {
                $table->decimal('rate', 10, 4)->default(1025)->after('received_qty');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'discount_qty')) {
                $table->decimal('discount_qty', 10, 3)->default(0)->after('rate');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'discount_rate')) {
                $table->decimal('discount_rate', 10, 4)->default(0)->after('discount_qty');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'units_count')) {
                $table->integer('units_count')->default(0)->after('discount_rate');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'transport_cost')) {
                $table->decimal('transport_cost', 10, 2)->default(0)->after('units_count');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'other_cost')) {
                $table->decimal('other_cost', 10, 2)->default(0)->after('transport_cost');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'total_cost')) {
                $table->decimal('total_cost', 10, 2)->default(0)->after('other_cost');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'supplier_contact_id')) {
                $table->foreignId('supplier_contact_id')->nullable()->after('total_cost')->constrained('contacts');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'cooling_season')) {
                $table->string('cooling_season')->nullable()->after('supplier_contact_id');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'delivery_order_id')) {
                $table->foreignId('delivery_order_id')->nullable()->after('cooling_season')->constrained('raw_delivery_orders');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'cooling_qty')) {
                $table->decimal('cooling_qty', 10, 3)->default(0)->after('delivery_order_id');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'cooling_rate')) {
                $table->decimal('cooling_rate', 10, 4)->default(1025)->after('cooling_qty');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'cooling_discount_qty')) {
                $table->decimal('cooling_discount_qty', 10, 3)->default(0)->after('cooling_rate');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'cooling_units_count')) {
                $table->integer('cooling_units_count')->default(0)->after('cooling_discount_qty');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'cooling_box_qty')) {
                $table->decimal('cooling_box_qty', 10, 3)->default(0)->after('cooling_units_count');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'cooling_price')) {
                $table->decimal('cooling_price', 10, 4)->default(0)->after('cooling_box_qty');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'cooling_total_qty')) {
                $table->decimal('cooling_total_qty', 10, 3)->default(0)->after('cooling_price');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'transport_contractor')) {
                $table->string('transport_contractor')->nullable()->after('cooling_total_qty');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'transport_units')) {
                $table->integer('transport_units')->default(0)->after('transport_contractor');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'transport_unit_cost')) {
                $table->decimal('transport_unit_cost', 10, 2)->default(0)->after('transport_units');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'transport_total')) {
                $table->decimal('transport_total', 10, 2)->default(0)->after('transport_unit_cost');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'price_per_intake')) {
                $table->decimal('price_per_intake', 10, 4)->default(0)->after('transport_total');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'intake_total')) {
                $table->decimal('intake_total', 10, 2)->default(0)->after('price_per_intake');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('status')->constrained('users');
            }
        });

        Schema::table('gate_inquiries', function (Blueprint $table): void {
            $table->string('accounting_period')->nullable()->after('reference_no');
            $table->string('branch')->nullable()->after('accounting_period');
            $table->date('entry_date')->nullable()->after('branch');
            $table->time('entry_time')->nullable()->after('entry_date');
            $table->string('code')->nullable()->after('entry_time');
            $table->string('raw_type')->nullable()->after('raw_material_type_id');
            $table->string('color')->nullable()->after('raw_type');
            $table->decimal('quantity', 10, 3)->default(0)->after('color');
            $table->string('reason')->nullable()->after('quantity');
            $table->string('vehicle_type')->nullable()->after('reason');
            $table->string('vehicle_size')->nullable()->after('driver_name');
            $table->decimal('counter_on_entry', 10, 2)->default(0)->after('vehicle_size');
            $table->decimal('counter_on_exit', 10, 2)->default(0)->after('counter_on_entry');
            $table->string('responsible_employee')->nullable()->after('counter_on_exit');
            $table->string('department')->nullable()->after('responsible_employee');
            $table->string('inquiry_status')->default('pending')->after('status');
            $table->text('cargo_desc_entry')->nullable()->after('inquiry_status');
            $table->text('cargo_desc_exit')->nullable()->after('cargo_desc_entry');
            $table->date('exit_date')->nullable()->after('cargo_desc_exit');
            $table->time('exit_time')->nullable()->after('exit_date');
            $table->foreignId('created_by')->nullable()->after('inquiry_status')->constrained('users');
        });

        Schema::table('scale_notes', function (Blueprint $table): void {
            if (!Schema::hasColumn('scale_notes', 'packhouse_id')) {
                $table->foreignId('packhouse_id')->nullable()->after('tenant_id')->constrained();
            }
            $table->string('accounting_period')->nullable()->after('reference_no');
            $table->string('branch')->nullable()->after('accounting_period');
            $table->date('note_date')->nullable()->after('branch');
            $table->time('note_time')->nullable()->after('note_date');
            $table->foreignId('contact_id')->nullable()->after('note_time')->constrained();
            $table->string('raw_type')->nullable()->after('contact_id');
            $table->string('cost_center')->nullable()->after('raw_type');
            $table->string('note_type')->nullable()->after('cost_center');
            $table->decimal('entry_count', 10, 2)->default(0)->after('note_type');
            $table->decimal('exit_count', 10, 2)->default(0)->after('entry_count');
            $table->decimal('box_weight', 10, 3)->default(1.75)->after('exit_count');
            $table->string('driver_name')->nullable()->after('box_weight');
            $table->string('vehicle_number')->nullable()->after('driver_name');
            $table->string('vehicle_type')->nullable()->after('vehicle_number');
            $table->string('farm_code')->nullable()->after('vehicle_type');
            $table->string('season')->nullable()->after('farm_code');
            $table->decimal('full_weight', 10, 3)->default(0)->after('season');
            $table->decimal('empty_weight', 10, 3)->default(0)->after('full_weight');
            $table->decimal('discount_1_pct', 5, 2)->default(0)->after('net_weight');
            $table->decimal('discount_1_val', 10, 3)->default(0)->after('discount_1_pct');
            $table->decimal('discount_2', 10, 3)->default(0)->after('discount_1_val');
            $table->decimal('discount_3', 10, 3)->default(0)->after('discount_2');
            $table->decimal('final_weight', 10, 3)->default(0)->after('discount_3');
            $table->integer('broken_boxes')->default(0)->after('final_weight');
            $table->integer('partial_boxes')->default(0)->after('broken_boxes');
            $table->string('harvest_contractor')->nullable()->after('partial_boxes');
            $table->decimal('harvest_pct', 5, 2)->default(0)->after('harvest_contractor');
            $table->text('inspection_report')->nullable()->after('harvest_pct');
            $table->string('status')->default('draft')->after('inspection_report');
            $table->foreignId('created_by')->nullable()->after('status')->constrained('users');
        });

        Schema::create('transport_cost_distributions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('packhouse_id')->nullable()->constrained();
            $table->string('branch')->nullable();
            $table->string('transport_contractor')->nullable();
            $table->date('date_from');
            $table->date('date_to');
            $table->boolean('include_previously_costed')->default(false);
            $table->boolean('include_farms')->default(false);
            $table->decimal('transport_cost', 10, 2)->default(0);
            $table->string('distribution_method')->default('weight');
            $table->json('receipt_ids')->nullable();
            $table->json('allocated_costs')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_cost_distributions');
    }
};
