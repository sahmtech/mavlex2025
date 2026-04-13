<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('UPDATE accounting_accounts_transactions SET cost_center_id = NULL WHERE cost_center_id IS NOT NULL AND cost_center_id NOT REGEXP "^[0-9]+$"');
        DB::statement('ALTER TABLE accounting_accounts_transactions MODIFY cost_center_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE accounting_accounts_transactions MODIFY cost_center_id TEXT NULL');
    }
};
