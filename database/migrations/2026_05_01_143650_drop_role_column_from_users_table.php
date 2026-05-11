<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        // Ensure there is an index for the FK on tenant_id before dropping a composite index.
        $hasTenantIndex = ! empty(DB::select(
            "select 1 from information_schema.statistics where table_schema = database() and table_name = 'users' and index_name = 'users_tenant_id_index' limit 1"
        ));

        if (! $hasTenantIndex) {
            DB::statement('alter table `users` add index `users_tenant_id_index` (`tenant_id`)');
        }

        $hasCompositeIndex = ! empty(DB::select(
            "select 1 from information_schema.statistics where table_schema = database() and table_name = 'users' and index_name = 'users_tenant_id_role_index' limit 1"
        ));

        if ($hasCompositeIndex) {
            DB::statement('alter table `users` drop index `users_tenant_id_role_index`');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('station_manager')->after('password');
        });

        // Recreate composite index if desired.
        $hasCompositeIndex = ! empty(DB::select(
            "select 1 from information_schema.statistics where table_schema = database() and table_name = 'users' and index_name = 'users_tenant_id_role_index' limit 1"
        ));

        if (! $hasCompositeIndex) {
            DB::statement('alter table `users` add index `users_tenant_id_role_index` (`tenant_id`, `role`)');
        }
    }
};
