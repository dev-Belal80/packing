<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('packhouse_id')->constrained();
            $table->string('reference_no');
            $table->foreignId('raw_receipt_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('production_line_id')->constrained();
            $table->foreignId('production_stage_id')->constrained();
            $table->foreignId('supervisor_id')->constrained('users');
            $table->decimal('target_qty_kg', 10, 3);
            $table->decimal('actual_input_kg', 10, 3)->default(0);
            $table->string('order_type')->default('own');
            $table->foreignId('client_contact_id')->nullable()->constrained('contacts');
            $table->string('status')->default('draft');
            $table->text('pause_reason')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference_no']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
