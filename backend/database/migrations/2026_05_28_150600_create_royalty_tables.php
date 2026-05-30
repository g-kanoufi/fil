<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('frequency', 11)->default('weekly')->index();
            $table->string('trigger_day', 11)->default('monday');
            $table->json('config')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'frequency']);
            $table->index(['area_id', 'frequency']);
        });

        Schema::create('royalty_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('royalty_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('frequency', 11)->default('weekly')->index();
            $table->dateTime('period_start')->index();
            $table->dateTime('period_end')->index();
            $table->dateTime('recorded_at');
            $table->decimal('gross_revenue', 18, 2)->nullable();
            $table->unsignedInteger('order_count')->default(0);
            $table->decimal('total_royalties', 18, 2)->nullable();
            $table->string('status', 32)->default('open')->index();
            $table->unsignedBigInteger('legacy_period_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['store_id', 'period_start', 'period_end', 'frequency'], 'royalty_periods_store_period_idx');
        });

        Schema::create('royalty_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('royalty_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('frequency', 11)->default('weekly');
            $table->string('trigger_day', 11)->default('monday');
            $table->string('royalty_type')->index();
            $table->string('royalty_name');
            $table->decimal('gross_revenue', 18, 2)->nullable();
            $table->float('royalty_rate');
            $table->decimal('royalty_amount', 18, 2)->nullable();
            $table->string('ach_source')->nullable();
            $table->string('ach_destination')->nullable();
            $table->string('funding_source')->nullable();
            $table->unsignedTinyInteger('payment_status')->default(0)->index();
            $table->foreignId('ach_transfer_id')->nullable()->constrained('ach_transfers')->nullOnDelete();
            $table->string('external_transfer_id')->nullable();
            $table->unsignedBigInteger('legacy_line_item_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['royalty_period_id', 'payment_status']);
            $table->index(['store_id', 'royalty_type']);
        });

        Schema::create('area_royalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->date('period')->index();
            $table->string('frequency')->nullable();
            $table->dateTime('recorded_at');
            $table->json('store_ids')->nullable();
            $table->json('royalty_line_item_ids')->nullable();
            $table->decimal('sum_total_sales', 18, 2)->default(0);
            $table->decimal('sum_unit_royalties', 18, 2)->default(0);
            $table->decimal('sum_area_royalties', 18, 2)->default(0);
            $table->decimal('sum_ach_available', 18, 2)->default(0);
            $table->float('percentage');
            $table->decimal('amount', 18, 2);
            $table->unsignedTinyInteger('payment_status')->default(0)->index();
            $table->string('ach_destination')->nullable();
            $table->foreignId('ach_transfer_id')->nullable()->constrained('ach_transfers')->nullOnDelete();
            $table->string('external_transfer_id')->nullable();
            $table->string('trigger_day', 11)->nullable();
            $table->text('errors')->nullable();
            $table->json('royalty_detail')->nullable();
            $table->unsignedBigInteger('legacy_area_royalty_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['area_id', 'period', 'frequency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_royalties');
        Schema::dropIfExists('royalty_line_items');
        Schema::dropIfExists('royalty_periods');
        Schema::dropIfExists('royalty_schedules');
    }
};
