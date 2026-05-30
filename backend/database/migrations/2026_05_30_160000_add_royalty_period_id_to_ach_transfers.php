<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ach_transfers', function (Blueprint $table): void {
            $table->foreignId('royalty_period_id')
                ->nullable()
                ->after('store_id')
                ->constrained('royalty_periods')
                ->nullOnDelete();

            $table->unique(['store_id', 'royalty_period_id']);
        });
    }

    public function down(): void
    {
        Schema::table('ach_transfers', function (Blueprint $table): void {
            $table->dropUnique(['store_id', 'royalty_period_id']);
            $table->dropConstrainedForeignId('royalty_period_id');
        });
    }
};
