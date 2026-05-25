<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_transactions', 'sort_record_id')) {
                $table->foreignId('sort_record_id')->nullable()->after('production_order_id')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('stock_transactions', 'sort_record_id')) {
                $table->dropForeign(['sort_record_id']);
                $table->dropColumn('sort_record_id');
            }
        });
    }
};
