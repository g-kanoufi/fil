<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 150)->nullable()->unique();
            $table->string('title');
            $table->string('trigger_slug')->index();
            $table->boolean('enabled')->default(false)->index();
            $table->json('config')->nullable();
            $table->json('extras')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->string('component')->nullable();
            $table->text('message');
            $table->timestamp('logged_at')->useCurrent()->index();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('legacy_log_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_rules');
    }
};
