<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gate_inquiries', function (Blueprint $table) {
            if (!Schema::hasColumn('gate_inquiries', 'delivery_order_id')) {
                return;
            }

            // Add FK only if it doesn't already exist.
            $table->foreign('delivery_order_id')
                ->references('id')
                ->on('raw_delivery_orders')
                ->nullOnDelete();

            $table->index(['tenant_id', 'delivery_order_id']);
        });
    }

    public function down(): void
    {
        Schema::table('gate_inquiries', function (Blueprint $table) {
            // Best-effort: drop FK/index if present.
            try {
                $table->dropForeign(['delivery_order_id']);
            } catch (Throwable $e) {
                // Ignore if not present.
            }
            try {
                $table->dropIndex(['tenant_id', 'delivery_order_id']);
            } catch (Throwable $e) {
                // Ignore if not present.
            }
        });
    }
};
