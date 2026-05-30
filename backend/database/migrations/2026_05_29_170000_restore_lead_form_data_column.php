<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('leads', 'form_data')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->json('form_data')->nullable()->after('eligible_for_drip');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('leads', 'form_data')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->dropColumn('form_data');
            });
        }
    }
};
