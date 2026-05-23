<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pallets', function (Blueprint $table) {
            if (! Schema::hasColumn('pallets', 'wooden_pallet_no')) {
                $table->string('wooden_pallet_no')->nullable()->after('reference_no');
            }
            if (! Schema::hasColumn('pallets', 'order_number')) {
                $table->string('order_number')->nullable()->after('wooden_pallet_no');
            }
            if (! Schema::hasColumn('pallets', 'final_order_no')) {
                $table->string('final_order_no')->nullable()->after('order_number');
            }
            if (! Schema::hasColumn('pallets', 'branch')) {
                $table->string('branch')->default('الرئيسي')->after('final_order_no');
            }

            if (! Schema::hasColumn('pallets', 'pallet_date')) {
                $table->date('pallet_date')->nullable()->after('branch');
            }
            if (! Schema::hasColumn('pallets', 'pallet_time')) {
                $table->time('pallet_time')->nullable()->after('pallet_date');
            }
            if (! Schema::hasColumn('pallets', 'end_date')) {
                $table->date('end_date')->nullable()->after('pallet_time');
            }
            if (! Schema::hasColumn('pallets', 'end_time')) {
                $table->time('end_time')->nullable()->after('end_date');
            }

            if (! Schema::hasColumn('pallets', 'production_line_id')) {
                $table->foreignId('production_line_id')->nullable()->constrained()->nullOnDelete()->after('sort_record_id');
            }
            if (! Schema::hasColumn('pallets', 'client_contact_id')) {
                $table->foreignId('client_contact_id')->nullable()->constrained('contacts')->nullOnDelete()->after('production_line_id');
            }
            if (! Schema::hasColumn('pallets', 'client_code')) {
                $table->string('client_code')->nullable()->after('client_contact_id');
            }
            if (! Schema::hasColumn('pallets', 'supplier_contact_id')) {
                $table->foreignId('supplier_contact_id')->nullable()->constrained('contacts')->nullOnDelete()->after('client_code');
            }
            if (! Schema::hasColumn('pallets', 'fridge_id')) {
                $table->foreignId('fridge_id')->nullable()->constrained()->nullOnDelete()->after('supplier_contact_id');
            }
            if (! Schema::hasColumn('pallets', 'brand_id')) {
                $table->unsignedBigInteger('brand_id')->nullable()->after('fridge_id');
            }
            if (! Schema::hasColumn('pallets', 'punnet_sticker_id')) {
                $table->unsignedBigInteger('punnet_sticker_id')->nullable()->after('brand_id');
            }

            if (! Schema::hasColumn('pallets', 'raw_type')) {
                $table->string('raw_type')->nullable()->after('punnet_sticker_id');
            }
            if (! Schema::hasColumn('pallets', 'package_type')) {
                $table->string('package_type')->nullable()->after('raw_type');
            }
            if (! Schema::hasColumn('pallets', 'size')) {
                $table->string('size')->nullable()->after('package_type');
            }
            if (! Schema::hasColumn('pallets', 'storage_location')) {
                $table->string('storage_location')->nullable()->after('grade');
            }

            if (! Schema::hasColumn('pallets', 'actual_weight')) {
                $table->decimal('actual_weight', 10, 3)->default(0)->after('storage_location');
            }
            if (! Schema::hasColumn('pallets', 'net_weight')) {
                $table->decimal('net_weight', 10, 3)->default(0)->after('actual_weight');
            }
            if (! Schema::hasColumn('pallets', 'weight_diff')) {
                $table->decimal('weight_diff', 10, 3)->default(0)->after('net_weight');
            }

            if (! Schema::hasColumn('pallets', 'cooling_start')) {
                $table->timestamp('cooling_start')->nullable()->after('weight_diff');
            }
            if (! Schema::hasColumn('pallets', 'cooling_end')) {
                $table->timestamp('cooling_end')->nullable()->after('cooling_start');
            }

            if (! Schema::hasColumn('pallets', 'stickers')) {
                $table->string('stickers')->nullable()->after('cooling_end');
            }
            if (! Schema::hasColumn('pallets', 'customer_lot_no')) {
                $table->string('customer_lot_no')->nullable()->after('stickers');
            }
            if (! Schema::hasColumn('pallets', 'has_carton')) {
                $table->boolean('has_carton')->default(false)->after('customer_lot_no');
            }
            if (! Schema::hasColumn('pallets', 'has_punnet')) {
                $table->boolean('has_punnet')->default(false)->after('has_carton');
            }
            if (! Schema::hasColumn('pallets', 'no_label')) {
                $table->boolean('no_label')->default(false)->after('has_punnet');
            }
            if (! Schema::hasColumn('pallets', 'original_pallet_ref')) {
                $table->string('original_pallet_ref')->nullable()->after('no_label');
            }
            if (! Schema::hasColumn('pallets', 'special_specs')) {
                $table->text('special_specs')->nullable()->after('original_pallet_ref');
            }

            if (! Schema::hasColumn('pallets', 'production_order_id')) {
                $table->foreignId('production_order_id')->nullable()->constrained()->nullOnDelete()->after('special_specs');
            }
            if (! Schema::hasColumn('pallets', 'is_shipped')) {
                $table->boolean('is_shipped')->default(false)->after('status');
            }
            if (! Schema::hasColumn('pallets', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('is_shipped');
            }

            foreach ([
                'pallets_tenant_id_pallet_date_index' => ['tenant_id', 'pallet_date'],
                'pallets_tenant_id_order_number_index' => ['tenant_id', 'order_number'],
                'pallets_tenant_id_client_contact_id_index' => ['tenant_id', 'client_contact_id'],
                'pallets_tenant_id_product_id_index' => ['tenant_id', 'product_id'],
                'pallets_tenant_id_production_line_id_index' => ['tenant_id', 'production_line_id'],
                'pallets_tenant_id_fridge_id_index' => ['tenant_id', 'fridge_id'],
            ] as $indexName => $columns) {
                $exists = DB::select(
                    "show index from pallets where Key_name = ?",
                    [$indexName]
                );

                if (empty($exists)) {
                    $table->index($columns, $indexName);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('pallets', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'pallet_date']);
            $table->dropIndex(['tenant_id', 'order_number']);
            $table->dropIndex(['tenant_id', 'client_contact_id']);
            $table->dropIndex(['tenant_id', 'product_id']);
            $table->dropIndex(['tenant_id', 'production_line_id']);
            $table->dropIndex(['tenant_id', 'fridge_id']);

            $table->dropForeign(['production_line_id']);
            $table->dropForeign(['client_contact_id']);
            $table->dropForeign(['supplier_contact_id']);
            $table->dropForeign(['fridge_id']);
            $table->dropForeign(['production_order_id']);
            $table->dropForeign(['created_by']);

            $table->dropColumn([
                'wooden_pallet_no',
                'order_number',
                'final_order_no',
                'branch',
                'pallet_date',
                'pallet_time',
                'end_date',
                'end_time',
                'production_line_id',
                'client_contact_id',
                'client_code',
                'supplier_contact_id',
                'fridge_id',
                'brand_id',
                'punnet_sticker_id',
                'raw_type',
                'package_type',
                'size',
                'storage_location',
                'actual_weight',
                'net_weight',
                'weight_diff',
                'cooling_start',
                'cooling_end',
                'stickers',
                'customer_lot_no',
                'has_carton',
                'has_punnet',
                'no_label',
                'original_pallet_ref',
                'special_specs',
                'production_order_id',
                'is_shipped',
                'created_by',
            ]);
        });
    }
};
