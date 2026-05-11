<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw statements to avoid requiring doctrine/dbal for change()
        DB::statement('ALTER TABLE `raw_receipts` MODIFY `contact_id` BIGINT UNSIGNED NULL;');
        DB::statement('ALTER TABLE `raw_receipts` MODIFY `raw_material_type_id` BIGINT UNSIGNED NULL;');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `raw_receipts` MODIFY `contact_id` BIGINT UNSIGNED NOT NULL;');
        DB::statement('ALTER TABLE `raw_receipts` MODIFY `raw_material_type_id` BIGINT UNSIGNED NOT NULL;');
    }
};
