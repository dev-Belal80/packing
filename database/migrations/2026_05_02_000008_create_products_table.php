<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('unit')->default('carton');
            $table->decimal('carton_weight_kg', 8, 3)->nullable();
            $table->json('grades')->nullable();
            $table->decimal('min_cooling_hours', 5, 2)->default(0);
            $table->decimal('waste_threshold_pct', 5, 2)->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
