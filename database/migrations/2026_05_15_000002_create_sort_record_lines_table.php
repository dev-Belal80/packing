<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sort_record_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('sort_record_id')->constrained()->cascadeOnDelete();

            $table->string('raw_type')->nullable();
            $table->foreignId('production_line_id')->nullable()->constrained();
            $table->string('lot_no')->nullable();
            $table->foreignId('production_order_id')->nullable()->constrained();

            $table->decimal('grade_a_kg', 10, 3)->default(0);
            $table->decimal('grade_b_kg', 10, 3)->default(0);
            $table->decimal('grade_c_kg', 10, 3)->default(0);
            $table->decimal('waste_kg', 10, 3)->default(0);
            $table->decimal('returned_kg', 10, 3)->default(0);
            $table->decimal('line_total', 10, 3)->default(0);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'sort_record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sort_record_lines');
    }
};
