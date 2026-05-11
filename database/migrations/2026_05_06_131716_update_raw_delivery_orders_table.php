<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('raw_delivery_orders', function (Blueprint $table) {
            // Drop old columns if they exist
            if (Schema::hasColumn('raw_delivery_orders', 'contact_id')) {
                $table->dropForeign(['contact_id']);
                $table->dropColumn('contact_id');
            }
            if (Schema::hasColumn('raw_delivery_orders', 'raw_material_type_id')) {
                $table->dropForeign(['raw_material_type_id']);
                $table->dropColumn('raw_material_type_id');
            }
            if (Schema::hasColumn('raw_delivery_orders', 'ordered_qty')) {
                $table->dropColumn('ordered_qty');
            }
            if (Schema::hasColumn('raw_delivery_orders', 'expected_date')) {
                $table->dropColumn('expected_date');
            }
        });

        Schema::table('raw_delivery_orders', function (Blueprint $table) {
            // Add packhouse_id if it doesn't exist
            if (!Schema::hasColumn('raw_delivery_orders', 'packhouse_id')) {
                $table->foreignId('packhouse_id')->after('tenant_id')->constrained();
            }

            // Header fields
            if (!Schema::hasColumn('raw_delivery_orders', 'year')) {
                $table->year('year')->after('reference_no')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'branch')) {
                $table->string('branch')->after('year')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'order_date')) {
                $table->date('order_date')->after('branch')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'agricultural_season')) {
                $table->string('agricultural_season')->after('order_date')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'work_order')) {
                $table->string('work_order')->after('agricultural_season')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'cost_center')) {
                $table->string('cost_center')->after('work_order')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'raw_type')) {
                $table->string('raw_type')->after('cost_center')->nullable();
            }

            // Locations
            if (!Schema::hasColumn('raw_delivery_orders', 'loading_warehouse')) {
                $table->string('loading_warehouse')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'destination_warehouse')) {
                $table->string('destination_warehouse')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'supplying_station')) {
                $table->string('supplying_station')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'delivery_station')) {
                $table->string('delivery_station')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'loading_warehouse_season')) {
                $table->string('loading_warehouse_season')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'supply_warehouse_season')) {
                $table->string('supply_warehouse_season')->nullable();
            }

            // Description
            if (!Schema::hasColumn('raw_delivery_orders', 'description_ar')) {
                $table->text('description_ar')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'description_en')) {
                $table->text('description_en')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'reference_number')) {
                $table->string('reference_number')->nullable();
            }

            // Weight
            if (!Schema::hasColumn('raw_delivery_orders', 'weight_on_entry')) {
                $table->decimal('weight_on_entry', 10, 3)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'weight_on_exit')) {
                $table->decimal('weight_on_exit', 10, 3)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'total_quantity')) {
                $table->decimal('total_quantity', 10, 3)->default(0);
            }

            // Sale section
            if (!Schema::hasColumn('raw_delivery_orders', 'client_contact_id')) {
                $table->foreignId('client_contact_id')->nullable()->constrained('contacts');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'sale_order')) {
                $table->string('sale_order')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'discount_pct')) {
                $table->decimal('discount_pct', 5, 4)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'discount_qty')) {
                $table->decimal('discount_qty', 10, 3)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'extra_discount_pct')) {
                $table->decimal('extra_discount_pct', 5, 4)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'extra_discount_qty')) {
                $table->decimal('extra_discount_qty', 10, 3)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'invoice_number')) {
                $table->string('invoice_number')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'net_qty')) {
                $table->decimal('net_qty', 10, 3)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'price_per_unit')) {
                $table->decimal('price_per_unit', 10, 4)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'units_count')) {
                $table->integer('units_count')->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'sorting_cost')) {
                $table->decimal('sorting_cost', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'sorting_cost_per_ton')) {
                $table->decimal('sorting_cost_per_ton', 10, 4)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'other_expenses')) {
                $table->decimal('other_expenses', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'supply_expenses')) {
                $table->decimal('supply_expenses', 10, 2)->default(0);
            }

            // Supply section
            if (!Schema::hasColumn('raw_delivery_orders', 'supplier_contact_id')) {
                $table->foreignId('supplier_contact_id')->nullable()->constrained('contacts');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'supply_order')) {
                $table->string('supply_order')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'supply_season')) {
                $table->string('supply_season')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'supply_qty')) {
                $table->decimal('supply_qty', 10, 3)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'supply_discount_pct')) {
                $table->decimal('supply_discount_pct', 5, 4)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'supply_discount_qty')) {
                $table->decimal('supply_discount_qty', 10, 3)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'net_supply_qty')) {
                $table->decimal('net_supply_qty', 10, 3)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'supplied_qty')) {
                $table->decimal('supplied_qty', 10, 3)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'cost_price')) {
                $table->decimal('cost_price', 10, 4)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'total_cost')) {
                $table->decimal('total_cost', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'supply_units_count')) {
                $table->integer('supply_units_count')->default(0);
            }

            // Transport section
            if (!Schema::hasColumn('raw_delivery_orders', 'transport_contractor')) {
                $table->string('transport_contractor')->nullable();
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'transport_units')) {
                $table->integer('transport_units')->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'transport_unit_cost')) {
                $table->decimal('transport_unit_cost', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'transport_total')) {
                $table->decimal('transport_total', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'transport_discount_qty')) {
                $table->decimal('transport_discount_qty', 10, 3)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'transport_price')) {
                $table->decimal('transport_price', 10, 4)->default(0);
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'transport_discount_value')) {
                $table->decimal('transport_discount_value', 10, 2)->default(0);
            }

            // Status and tracking
            if (!Schema::hasColumn('raw_delivery_orders', 'created_by')) {
                $table->foreignId('created_by')->constrained('users');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'confirmed_by')) {
                $table->foreignId('confirmed_by')->nullable()->constrained('users');
            }
            if (!Schema::hasColumn('raw_delivery_orders', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable();
            }

            // Change status column if it exists
            if (Schema::hasColumn('raw_delivery_orders', 'status')) {
                $table->string('status')->default('draft')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_delivery_orders', function (Blueprint $table) {
            // Revert by adding back old columns (optional, based on needs)
        });
    }
};
