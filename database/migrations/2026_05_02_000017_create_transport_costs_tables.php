<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transport_costs')) {
            Schema::create('transport_costs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained();
                $table->decimal('total_cost', 10, 2);
                $table->string('distribution_method')->default('weight');
                $table->date('distribution_date');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'distribution_date'], 'tc_tenant_date_idx');
            });
        }

        if (! Schema::hasTable('transport_cost_receipts')) {
            Schema::create('transport_cost_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained();
                $table->foreignId('transport_cost_id')->constrained()->cascadeOnDelete();
                $table->foreignId('raw_receipt_id')->constrained()->cascadeOnDelete();
                $table->decimal('allocated_cost', 10, 2);
                $table->timestamps();

                // Keep index names short for MySQL (64 char limit)
                $table->unique(['tenant_id', 'transport_cost_id', 'raw_receipt_id'], 'tcr_tc_rr_uq');
                $table->index(['tenant_id', 'raw_receipt_id'], 'tcr_tenant_rr_idx');
            });

            return;
        }

        // Recovery path (table was created before migration failed).
        Schema::table('transport_cost_receipts', function (Blueprint $table) {
            if (! Schema::hasColumn('transport_cost_receipts', 'tenant_id')) {
                $table->foreignId('tenant_id')->constrained()->after('id');
            }
        });

        try {
            DB::statement('alter table `transport_cost_receipts` add unique `tcr_tc_rr_uq` (`tenant_id`, `transport_cost_id`, `raw_receipt_id`)');
        } catch (Throwable $e) {
            // Intentionally ignored.
        }

        try {
            DB::statement('alter table `transport_cost_receipts` add index `tcr_tenant_rr_idx` (`tenant_id`, `raw_receipt_id`)');
        } catch (Throwable $e) {
            // Intentionally ignored.
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_cost_receipts');
        Schema::dropIfExists('transport_costs');
    }
};
