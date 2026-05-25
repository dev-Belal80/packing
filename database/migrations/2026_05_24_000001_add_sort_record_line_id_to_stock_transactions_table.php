<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table): void {
            $table->foreignId('sort_record_line_id')
                ->nullable()
                ->after('production_order_id')
                ->constrained('sort_record_lines')
                ->nullOnDelete();

            $table->unique(['sort_record_line_id', 'type', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table): void {
            $table->dropUnique(['sort_record_line_id', 'type', 'reason']);
            $table->dropConstrainedForeignId('sort_record_line_id');
        });
    }
};
