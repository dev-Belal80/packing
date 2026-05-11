<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_order_pickings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('production_order_id')->constrained();
            $table->foreignId('raw_receipt_id')->constrained();
            $table->decimal('dispatched_qty_kg', 10, 3);
            $table->foreignId('dispatched_by')->constrained('users');
            $table->timestamp('dispatched_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'production_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_pickings');
    }
};
