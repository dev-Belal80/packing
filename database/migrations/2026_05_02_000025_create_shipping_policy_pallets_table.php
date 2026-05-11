<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_policy_pallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('shipping_policy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pallet_id')->constrained();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'shipping_policy_id', 'pallet_id'], 'spp_pol_pal_uq');
            $table->index(['tenant_id', 'pallet_id'], 'spp_tenant_pal_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_policy_pallets');
    }
};
