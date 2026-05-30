<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('closings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->date('closing_date')->nullable()->index();
            $table->string('status', 32)->default('pending')->index();
            $table->json('extras')->nullable();
            $table->unsignedBigInteger('legacy_post_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('ai_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('external_thread_id')->nullable()->index();
            $table->string('status', 32)->default('active')->index();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('legacy_chat_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_thread_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32)->index();
            $table->text('content');
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ai_thread_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_threads');
        Schema::dropIfExists('closings');
    }
};
