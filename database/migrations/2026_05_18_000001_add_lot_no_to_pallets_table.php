<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pallets', function (Blueprint $table): void {
            if (! Schema::hasColumn('pallets', 'lot_no')) {
                $table->string('lot_no')->nullable()->after('supplier_contact_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pallets', function (Blueprint $table): void {
            if (Schema::hasColumn('pallets', 'lot_no')) {
                $table->dropColumn('lot_no');
            }
        });
    }
};