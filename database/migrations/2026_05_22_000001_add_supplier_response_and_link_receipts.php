<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_delivery_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('raw_delivery_orders', 'supplier_response')) {
                $table->string('supplier_response')->default('pending')->after('status'); // pending|accepted|rejected
            }
            if (! Schema::hasColumn('raw_delivery_orders', 'supplier_responded_by')) {
                $table->foreignId('supplier_responded_by')->nullable()->after('supplier_response')->constrained('users');
            }
            if (! Schema::hasColumn('raw_delivery_orders', 'supplier_responded_at')) {
                $table->timestamp('supplier_responded_at')->nullable()->after('supplier_responded_by');
            }
            if (! Schema::hasColumn('raw_delivery_orders', 'supplier_notes')) {
                $table->text('supplier_notes')->nullable()->after('supplier_responded_at');
            }
        });

        Schema::table('raw_receipts', function (Blueprint $table) {
            if (! Schema::hasColumn('raw_receipts', 'raw_delivery_order_id')) {
                $table->foreignId('raw_delivery_order_id')->nullable()->after('scale_note_id')->constrained('raw_delivery_orders');
            }
        });
    }

    public function down(): void
    {
        Schema::table('raw_delivery_orders', function (Blueprint $table) {
            if (Schema::hasColumn('raw_delivery_orders', 'supplier_notes')) {
                $table->dropColumn('supplier_notes');
            }
            if (Schema::hasColumn('raw_delivery_orders', 'supplier_responded_at')) {
                $table->dropColumn('supplier_responded_at');
            }
            if (Schema::hasColumn('raw_delivery_orders', 'supplier_responded_by')) {
                $table->dropForeign(['supplier_responded_by']);
                $table->dropColumn('supplier_responded_by');
            }
            if (Schema::hasColumn('raw_delivery_orders', 'supplier_response')) {
                $table->dropColumn('supplier_response');
            }
        });

        Schema::table('raw_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('raw_receipts', 'raw_delivery_order_id')) {
                $table->dropForeign(['raw_delivery_order_id']);
                $table->dropColumn('raw_delivery_order_id');
            }
        });
    }
};
