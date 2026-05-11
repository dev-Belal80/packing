<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->string('type');
            $table->string('reason');
            $table->foreignId('raw_receipt_id')->nullable()->constrained();
            $table->foreignId('production_order_id')->nullable()->constrained();
            $table->foreignId('shipping_policy_id')->nullable()->constrained();
            $table->decimal('quantity_kg', 10, 3);
            $table->decimal('unit_cost', 10, 4)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'type', 'reason']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
