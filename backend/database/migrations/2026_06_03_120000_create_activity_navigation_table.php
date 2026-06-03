<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_navigation', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('actor_name');
            $table->string('path_key', 255);
            $table->date('period_bucket')->index();
            $table->string('subject_type', 32)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->timestampTz('first_seen_at');
            $table->timestampTz('last_seen_at')->index();
            $table->unsignedInteger('view_count')->default(1);
            $table->string('source', 32)->default('app');
            $table->timestamps();

            $table->unique(['actor_user_id', 'path_key', 'period_bucket']);
            $table->index(['actor_user_id', 'last_seen_at']);
            $table->index(['subject_type', 'subject_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_navigation');
    }
};
