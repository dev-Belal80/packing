<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_receipts', function (Blueprint $table) {
            $table->string('accounting_period', 20)->nullable()->after('reference_no');
            $table->string('receipt_permission_number', 50)->nullable()->after('accounting_period');
            $table->string('balance_record_number', 50)->nullable()->after('receipt_permission_number');
            $table->date('receipt_date')->nullable()->after('balance_record_number');
            $table->string('branch')->nullable()->after('receipt_date');
            $table->string('packing_station')->nullable()->after('branch');
            $table->string('farm')->nullable()->after('packing_station');
            $table->string('item_type')->nullable()->after('farm');
            $table->decimal('discount', 10, 2)->nullable()->after('item_type');
            $table->string('reception_unit', 50)->nullable()->after('discount');
            $table->string('collection_unit', 50)->nullable()->after('reception_unit');
            $table->decimal('quantity_per_box', 10, 3)->nullable()->after('collection_unit');
            $table->string('pallet_type')->nullable()->after('quantity_per_box');
            $table->unsignedInteger('pallets_count')->nullable()->after('pallet_type');
            $table->decimal('produced_quantity', 10, 3)->nullable()->after('pallets_count');
            $table->decimal('sorting_and_loss', 10, 3)->nullable()->after('produced_quantity');
            $table->decimal('used_quantity', 10, 3)->nullable()->after('sorting_and_loss');
            $table->string('inspector')->nullable()->after('used_quantity');
            $table->string('vehicle_number')->nullable()->after('inspector');
            $table->string('driver_name')->nullable()->after('vehicle_number');
            $table->string('transport_type')->nullable()->after('driver_name');
            $table->string('vehicle_type')->nullable()->after('transport_type');
            $table->string('transport_contractor')->nullable()->after('vehicle_type');
            $table->decimal('entry_weight', 10, 3)->nullable()->after('transport_contractor');
            $table->decimal('exit_weight', 10, 3)->nullable()->after('entry_weight');
            $table->timestamp('exit_date_time')->nullable()->after('exit_weight');
        });
    }

    public function down(): void
    {
        Schema::table('raw_receipts', function (Blueprint $table) {
            $table->dropColumn([
                'vehicle_number',
                'driver_name',
                'transport_type',
                'vehicle_type',
                'transport_contractor',
                'entry_weight',
                'exit_weight',
                'exit_date_time',
                'accounting_period',
                'receipt_permission_number',
                'balance_record_number',
                'receipt_date',
                'branch',
                'packing_station',
                'farm',
                'item_type',
                'discount',
                'reception_unit',
                'collection_unit',
                'quantity_per_box',
                'pallet_type',
                'pallets_count',
                'produced_quantity',
                'sorting_and_loss',
                'used_quantity',
                'inspector',
            ]);
        });
    }
};
