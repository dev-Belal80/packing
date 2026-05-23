<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sort_records', function (Blueprint $table) {
            $table->dropForeign(['production_order_id']);
            $table->dropForeign(['recorded_by']);
            $table->dropColumn([
                'production_order_id',
                'grade_a_kg',
                'grade_b_kg',
                'grade_c_kg',
                'normal_waste_kg',
                'damaged_kg',
                'damage_reason',
                'total_output_kg',
                'started_at',
                'ended_at',
                'recorded_by',
            ]);
        });

        Schema::table('sort_records', function (Blueprint $table) {
            $table->foreignId('packhouse_id')->after('tenant_id')->constrained();

            $table->string('reference_no')->unique()->after('packhouse_id');
            $table->string('accounting_period')->nullable()->after('reference_no');
            $table->string('branch')->default('الرئيسي')->after('accounting_period');
            $table->date('sort_date')->after('branch');
            $table->time('sort_time')->nullable()->after('sort_date');
            $table->text('description_ar')->nullable()->after('sort_time');
            $table->text('description_en')->nullable()->after('description_ar');
            $table->text('notes')->nullable()->after('description_en');

            $table->decimal('total_grade_a', 10, 3)->default(0)->after('notes');
            $table->decimal('total_grade_b', 10, 3)->default(0)->after('total_grade_a');
            $table->decimal('total_grade_c', 10, 3)->default(0)->after('total_grade_b');
            $table->decimal('total_waste', 10, 3)->default(0)->after('total_grade_c');
            $table->decimal('total_returned', 10, 3)->default(0)->after('total_waste');
            $table->decimal('total_sort', 10, 3)->default(0)->after('total_returned');

            $table->string('status')->default('draft')->after('total_sort');
            $table->foreignId('posted_by')->nullable()->after('status')->constrained('users');
            $table->timestamp('posted_at')->nullable()->after('posted_by');
            $table->foreignId('created_by')->after('posted_at')->constrained('users');

            $table->index(['tenant_id', 'sort_date']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('sort_records', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'sort_date']);
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropForeign(['packhouse_id']);
            $table->dropForeign(['posted_by']);
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'packhouse_id',
                'reference_no',
                'accounting_period',
                'branch',
                'sort_date',
                'sort_time',
                'description_ar',
                'description_en',
                'notes',
                'total_grade_a',
                'total_grade_b',
                'total_grade_c',
                'total_waste',
                'total_returned',
                'total_sort',
                'status',
                'posted_by',
                'posted_at',
                'created_by',
            ]);
        });

        Schema::table('sort_records', function (Blueprint $table) {
            $table->foreignId('production_order_id')->constrained();
            $table->decimal('grade_a_kg', 10, 3)->default(0);
            $table->decimal('grade_b_kg', 10, 3)->default(0);
            $table->decimal('grade_c_kg', 10, 3)->default(0);
            $table->decimal('normal_waste_kg', 10, 3)->default(0);
            $table->decimal('damaged_kg', 10, 3)->default(0);
            $table->string('damage_reason')->nullable();
            $table->decimal('total_output_kg', 10, 3)->storedAs(
                'grade_a_kg + grade_b_kg + grade_c_kg + normal_waste_kg + damaged_kg'
            );
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('recorded_by')->constrained('users');

            $table->index(['tenant_id', 'production_order_id']);
            $table->index(['tenant_id', 'created_at']);
        });
    }
};
