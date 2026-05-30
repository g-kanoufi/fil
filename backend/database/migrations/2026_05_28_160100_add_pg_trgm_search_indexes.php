<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX IF NOT EXISTS leads_title_trgm_idx ON leads USING gin (title gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS stores_name_trgm_idx ON stores USING gin (name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS users_name_trgm_idx ON users USING gin (name gin_trgm_ops)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS leads_title_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS stores_name_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS users_name_trgm_idx');
    }
};
