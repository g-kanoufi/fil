<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_events', function (Blueprint $table): void {
            $table->id();
            $table->timestampTz('occurred_at')->useCurrent()->index();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('category', 32);
            $table->string('action', 64);
            $table->text('summary');
            $table->string('subject_type', 32)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('object_type', 32)->nullable();
            $table->unsignedBigInteger('object_id')->nullable();
            $table->string('source', 32)->default('app');
            $table->uuid('request_id')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('legacy_stream_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['actor_user_id', 'occurred_at']);
            $table->index(['subject_type', 'subject_id', 'occurred_at']);
            $table->index(['category', 'occurred_at']);
        });

        Schema::create('activity_events_archive', function (Blueprint $table): void {
            $table->id();
            $table->timestampTz('occurred_at')->index();
            $table->foreignId('actor_user_id')->nullable();
            $table->string('actor_name');
            $table->string('category', 32);
            $table->string('action', 64);
            $table->text('summary');
            $table->string('subject_type', 32)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('object_type', 32)->nullable();
            $table->unsignedBigInteger('object_id')->nullable();
            $table->string('source', 32)->default('app');
            $table->uuid('request_id')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('legacy_stream_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_events_archive');
        Schema::dropIfExists('activity_events');
    }
};
