<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('production_orders', 'accounting_period')) {
                $table->string('accounting_period')->nullable()->after('reference_no');
            }
            if (! Schema::hasColumn('production_orders', 'branch')) {
                $table->string('branch')->nullable()->after('accounting_period');
            }
            if (! Schema::hasColumn('production_orders', 'order_date')) {
                $table->date('order_date')->nullable()->after('branch');
            }
            if (! Schema::hasColumn('production_orders', 'supplier_contact_id')) {
                $table->foreignId('supplier_contact_id')->nullable()->after('production_stage_id')->constrained('contacts');
            }
            if (! Schema::hasColumn('production_orders', 'pallet_type_id')) {
                $table->foreignId('pallet_type_id')->nullable()->after('product_id')->constrained('pallet_types');
            }
            if (! Schema::hasColumn('production_orders', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (! Schema::hasColumn('production_orders', 'other_supervisor_ids')) {
                $table->json('other_supervisor_ids')->nullable()->after('supervisor_id');
            }
            if (! Schema::hasColumn('production_orders', 'special_code')) {
                $table->string('special_code')->nullable()->after('reference_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table): void {
            if (Schema::hasColumn('production_orders', 'other_supervisor_ids')) {
                $table->dropColumn('other_supervisor_ids');
            }
            if (Schema::hasColumn('production_orders', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('production_orders', 'supplier_contact_id')) {
                $table->dropConstrainedForeignId('supplier_contact_id');
            }
            if (Schema::hasColumn('production_orders', 'pallet_type_id')) {
                $table->dropConstrainedForeignId('pallet_type_id');
            }
            if (Schema::hasColumn('production_orders', 'order_date')) {
                $table->dropColumn('order_date');
            }
            if (Schema::hasColumn('production_orders', 'branch')) {
                $table->dropColumn('branch');
            }
            if (Schema::hasColumn('production_orders', 'accounting_period')) {
                $table->dropColumn('accounting_period');
            }
            if (Schema::hasColumn('production_orders', 'special_code')) {
                $table->dropColumn('special_code');
            }
        });
    }
};
