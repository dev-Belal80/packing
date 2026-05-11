<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('packhouse_id')->constrained();
            $table->string('reference_no');
            $table->foreignId('importer_contact_id')->constrained('contacts');
            $table->string('destination_country');
            $table->string('container_number');
            $table->string('vessel_name')->nullable();
            $table->date('shipping_date');
            $table->decimal('total_pallets', 8, 0)->default(0);
            $table->decimal('total_cartons', 10, 0)->default(0);
            $table->decimal('total_weight_kg', 10, 3)->default(0);
            $table->string('status')->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference_no']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'shipping_date']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_policies');
    }
};
