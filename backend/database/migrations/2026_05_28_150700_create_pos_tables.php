<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32)->index();
            $table->string('external_location_id')->nullable()->index();
            $table->json('credentials')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamp('last_synced_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'provider']);
        });

        Schema::create('pos_sales_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date')->index();
            $table->dateTime('period_start')->nullable();
            $table->dateTime('period_end')->nullable();
            $table->decimal('gross_sales', 18, 2)->nullable();
            $table->unsignedInteger('order_count')->default(0);
            $table->json('raw_payload')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'snapshot_date']);
            $table->index(['pos_connection_id', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sales_snapshots');
        Schema::dropIfExists('pos_connections');
    }
};
