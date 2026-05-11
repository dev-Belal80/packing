<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('packhouse_id')->constrained();
            $table->string('reference_no'); // GI-YYYYMMDD-XXXX
            $table->string('vehicle_number');
            $table->string('driver_name')->nullable();
            $table->foreignId('contact_id')->constrained();
            $table->foreignId('raw_material_type_id')->constrained();
            $table->decimal('expected_qty', 10, 3)->nullable();

            // Create column now; FK is added later because raw_delivery_orders is created after this migration.
            $table->foreignId('delivery_order_id')->nullable();

            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference_no']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_inquiries');
    }
};
