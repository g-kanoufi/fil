<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_opening_checklist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('item_key');
            $table->string('label');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'item_key']);
        });

        Schema::table('stores', function (Blueprint $table): void {
            $table->date('buildout_started_at')->nullable()->after('store_status');
            $table->date('expected_opening_at')->nullable()->after('buildout_started_at');
            $table->date('opened_at')->nullable()->after('expected_opening_at');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropColumn(['buildout_started_at', 'expected_opening_at', 'opened_at']);
        });

        Schema::dropIfExists('store_opening_checklist_items');
    }
};
