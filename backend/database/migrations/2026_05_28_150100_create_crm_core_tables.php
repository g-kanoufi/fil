<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->string('status', 32)->default('active')->index();
            $table->json('extras')->nullable();
            $table->unsignedBigInteger('legacy_post_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->string('status', 32)->default('active')->index();
            $table->string('approval_status', 32)->nullable()->index();
            $table->json('territory')->nullable();
            $table->json('extras')->nullable();
            $table->unsignedBigInteger('legacy_post_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->nullable()->index();
            $table->foreignId('prospect_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('pipeline_phase')->default(1)->index();
            $table->string('lead_status')->nullable()->index();
            $table->string('lead_stage')->nullable()->index();
            $table->timestamp('disclosed_at')->nullable();
            $table->timestamp('nda_signed_at')->nullable();
            $table->timestamp('fdd_signed_at')->nullable();
            $table->timestamp('waiting_period_ends_at')->nullable();
            $table->unsignedBigInteger('drip_campaign_id')->nullable()->index();
            $table->boolean('eligible_for_drip')->default(false);
            $table->json('form_data')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->unsignedBigInteger('legacy_post_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('lead_phase_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('from_phase')->nullable();
            $table->unsignedTinyInteger('to_phase');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 32)->default('active')->index();
            $table->string('spa_id')->nullable()->index();
            $table->string('pos_provider', 32)->nullable()->index();
            $table->string('pos_external_id')->nullable();
            $table->json('royalty_config')->nullable();
            $table->json('extras')->nullable();
            $table->unsignedBigInteger('legacy_post_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('store_owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('ownership_pct', 5, 2)->nullable();
            $table->string('role', 64)->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_owners');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('lead_phase_events');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('organizations');
    }
};
