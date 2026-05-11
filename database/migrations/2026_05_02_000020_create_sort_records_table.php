<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sort_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('production_order_id')->constrained();
            $table->decimal('grade_a_kg', 10, 3)->default(0);
            $table->decimal('grade_b_kg', 10, 3)->default(0);
            $table->decimal('grade_c_kg', 10, 3)->default(0);
            $table->decimal('normal_waste_kg', 10, 3)->default(0);
            $table->decimal('damaged_kg', 10, 3)->default(0);
            $table->string('damage_reason')->nullable();
            $table->decimal('total_output_kg', 10, 3)->storedAs(
                'grade_a_kg + grade_b_kg + grade_c_kg + normal_waste_kg + damaged_kg'
            );
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('has_waste_alert')->default(false);
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'production_order_id']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sort_records');
    }
};
