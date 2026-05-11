<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('packhouse_id')->constrained();
            $table->string('employee_name');
            $table->foreignId('job_title_id')->constrained();
            $table->date('attendance_date');
            $table->time('check_in')->nullable();
            $table->decimal('hours_worked', 5, 2)->default(8);
            $table->decimal('calculated_wage', 10, 2)->default(0);
            $table->boolean('is_present')->default(true);
            $table->string('absence_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_attendance');
    }
};
