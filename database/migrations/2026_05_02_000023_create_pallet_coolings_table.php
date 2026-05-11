<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pallet_coolings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('pallet_id')->constrained();
            $table->foreignId('fridge_id')->constrained();
            $table->decimal('entry_temp', 5, 2)->nullable();
            $table->timestamp('entered_at');
            $table->timestamp('ready_at')->nullable();
            $table->boolean('has_temp_alert')->default(false);
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'pallet_id']);
            $table->index(['tenant_id', 'entered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pallet_coolings');
    }
};
