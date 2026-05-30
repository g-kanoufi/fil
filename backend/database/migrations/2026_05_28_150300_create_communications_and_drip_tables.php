<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            $table->string('external_message_id')->nullable()->index();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_name')->nullable();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name')->nullable();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32)->index();
            $table->string('direction', 16)->index();
            $table->text('message');
            $table->json('meta')->nullable();
            $table->string('provider', 64)->nullable()->index();
            $table->timestamp('sent_at')->useCurrent()->index();
            $table->string('status', 32)->index();
            $table->text('errors')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'type', 'sent_at']);
        });

        Schema::create('drip_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->string('trigger_event', 64)->nullable()->index();
            $table->json('extras')->nullable();
            $table->timestamps();
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->foreign('drip_campaign_id')
                ->references('id')
                ->on('drip_campaigns')
                ->nullOnDelete();
        });

        Schema::create('drip_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drip_campaign_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedSmallInteger('delay_days')->default(0);
            $table->unsignedSmallInteger('delay_hours')->default(0);
            $table->string('channel', 32)->default('email')->index();
            $table->string('subject')->nullable();
            $table->text('body_template')->nullable();
            $table->string('template_id')->nullable();
            $table->json('conditions')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();

            $table->index(['drip_campaign_id', 'sort_order']);
        });

        Schema::create('drip_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drip_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->timestamp('enrolled_at')->useCurrent()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->foreignId('current_step_id')->nullable()->constrained('drip_steps')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['lead_id', 'drip_campaign_id']);
        });

        Schema::create('drip_step_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drip_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drip_step_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scheduled_at')->index();
            $table->timestamp('sent_at')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->foreignId('communication_id')->nullable()->constrained('communications')->nullOnDelete();
            $table->text('error')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['drip_enrollment_id', 'drip_step_id']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_step_runs');
        Schema::dropIfExists('drip_enrollments');
        Schema::dropIfExists('drip_steps');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['drip_campaign_id']);
        });

        Schema::dropIfExists('drip_campaigns');
        Schema::dropIfExists('communications');
    }
};
