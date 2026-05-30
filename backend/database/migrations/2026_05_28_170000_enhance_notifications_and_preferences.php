<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_rules', function (Blueprint $table) {
            $table->string('channel')->default('email')->after('trigger_slug');
            $table->string('subject')->nullable()->after('channel');
            $table->longText('body_html')->nullable()->after('subject');
            $table->json('recipients')->nullable()->after('body_html');
            $table->json('conditionals')->nullable()->after('recipients');
            $table->json('profile_roles')->nullable()->after('conditionals');
        });

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('trigger_slug')->index();
            $table->string('channel')->default('email');
            $table->string('status')->default('pending')->index();
            $table->string('recipient_email')->nullable()->index();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject')->nullable();
            $table->text('error')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['notification_rule_id', 'lead_id', 'status']);
        });

        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('notification_rule_id')->constrained()->cascadeOnDelete();
            $table->boolean('opted_in')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'notification_rule_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
        Schema::dropIfExists('notification_deliveries');
        Schema::table('notification_rules', function (Blueprint $table) {
            $table->dropColumn([
                'channel',
                'subject',
                'body_html',
                'recipients',
                'conditionals',
                'profile_roles',
            ]);
        });
    }
};
