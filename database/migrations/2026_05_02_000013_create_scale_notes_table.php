<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scale_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->string('reference_no');
            $table->foreignId('gate_inquiry_id')->nullable()->constrained();
            $table->decimal('gross_weight', 10, 3);
            $table->decimal('tare_weight', 10, 3)->nullable();
            $table->decimal('net_weight', 10, 3)->nullable();
            $table->boolean('is_manual')->default(false);
            $table->string('manual_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference_no']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scale_notes');
    }
};
