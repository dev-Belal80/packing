<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'plan')) {
                $table->string('plan')->default('basic')->after('location');
            }

            if (! Schema::hasColumn('tenants', 'subscription_ends_at')) {
                $table->timestamp('subscription_ends_at')->nullable()->after('plan');
            }

            if (! Schema::hasColumn('tenants', 'max_users')) {
                $table->unsignedInteger('max_users')->default(5)->after('subscription_ends_at');
            }

            if (! Schema::hasColumn('tenants', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('max_users');
            }

            if (! Schema::hasColumn('tenants', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Best-effort defaults without requiring doctrine/dbal.
        DB::statement("update `tenants` set `plan` = 'basic' where `plan` is null");
        DB::statement("update `tenants` set `max_users` = 5 where `max_users` is null");

        // Set defaults at the DB level when supported.
        try {
            DB::statement("alter table `tenants` alter `plan` set default 'basic'");
        } catch (Throwable $e) {
            // Ignore if the database doesn't support this syntax.
        }

        try {
            DB::statement("alter table `tenants` alter `max_users` set default 5");
        } catch (Throwable $e) {
            // Ignore if the database doesn't support this syntax.
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
