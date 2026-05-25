<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('raw_receipts', function (Blueprint $table) {
            if (! Schema::hasColumn('raw_receipts', 'reserved_qty')) {
                $table->decimal('reserved_qty', 10, 3)->default(0)->after('quantity_kg');
            }
            if (! Schema::hasColumn('raw_receipts', 'dispatched_qty')) {
                $table->decimal('dispatched_qty', 10, 3)->default(0)->after('reserved_qty');
            }
            if (! Schema::hasColumn('raw_receipts', 'consumed_qty')) {
                $table->decimal('consumed_qty', 10, 3)->default(0)->after('dispatched_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('raw_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('raw_receipts', 'consumed_qty')) {
                $table->dropColumn('consumed_qty');
            }
            if (Schema::hasColumn('raw_receipts', 'dispatched_qty')) {
                $table->dropColumn('dispatched_qty');
            }
            if (Schema::hasColumn('raw_receipts', 'reserved_qty')) {
                $table->dropColumn('reserved_qty');
            }
        });
    }
};
