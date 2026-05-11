<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('packhouse_id')->constrained();
            $table->string('reference_no');
            $table->foreignId('contact_id')->constrained();
            $table->string('contact_role');
            $table->foreignId('raw_material_type_id')->constrained();
            $table->foreignId('gate_inquiry_id')->nullable()->constrained();
            $table->foreignId('scale_note_id')->nullable()->constrained();
            $table->unsignedInteger('boxes_count')->nullable();
            $table->decimal('quantity_kg', 10, 3);
            $table->string('quality_result');
            $table->text('quality_notes')->nullable();
            $table->boolean('is_partial')->default(false);
            $table->boolean('has_weight_dispute')->default(false);
            $table->text('weight_dispute_notes')->nullable();

            $table->decimal('price_per_kg', 10, 4)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->decimal('transport_cost', 10, 2)->default(0);

            $table->string('status')->default('pending');
            $table->string('approval_status')->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference_no']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'approval_status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_receipts');
    }
};
