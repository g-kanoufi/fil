<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ach_customers', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('provider', 32)->default('dwolla')->index();
            $table->string('external_customer_id')->index();
            $table->string('status', 32)->default('active')->index();
            $table->json('profile')->nullable();
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id', 'provider'], 'ach_customers_owner_provider_unique');
        });

        Schema::create('ach_funding_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ach_customer_id')->constrained()->cascadeOnDelete();
            $table->string('external_funding_source_id')->index();
            $table->string('name')->nullable();
            $table->string('type', 32)->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->boolean('is_default')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['ach_customer_id', 'is_default']);
        });

        Schema::create('ach_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('transferred_at')->index();
            $table->foreignId('source_funding_source_id')->nullable()->constrained('ach_funding_sources')->nullOnDelete();
            $table->foreignId('destination_funding_source_id')->nullable()->constrained('ach_funding_sources')->nullOnDelete();
            $table->string('external_transfer_id')->nullable()->index();
            $table->string('provider', 32)->default('dwolla')->index();
            $table->string('provider_status', 32)->nullable()->index();
            $table->unsignedTinyInteger('status')->default(0)->index();
            $table->decimal('amount', 18, 2);
            $table->string('royalty_name')->nullable();
            $table->string('description')->nullable();
            $table->string('addenda', 80)->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->text('errors')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('legacy_transfer_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['store_id', 'transferred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ach_transfers');
        Schema::dropIfExists('ach_funding_sources');
        Schema::dropIfExists('ach_customers');
    }
};
