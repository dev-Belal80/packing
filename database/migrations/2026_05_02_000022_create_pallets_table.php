<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('packhouse_id')->constrained();
            $table->string('reference_no');
            $table->foreignId('pallet_type_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('sort_record_id')->constrained();
            $table->string('grade');
            $table->unsignedInteger('cartons_count');
            $table->decimal('total_weight_kg', 10, 3);
            $table->string('status')->default('building');
            $table->boolean('receipt_confirmed')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference_no']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pallets');
    }
};
