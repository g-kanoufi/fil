<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->nullable()->unique();
            $table->string('mime_type', 128)->nullable();
            $table->string('storage_disk', 32)->default('local');
            $table->string('storage_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->unsignedSmallInteger('version')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('extras')->nullable();
            $table->unsignedBigInteger('legacy_post_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('fdds', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->index();
            $table->string('title');
            $table->string('slug')->nullable()->unique();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('version')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->json('extras')->nullable();
            $table->unsignedBigInteger('legacy_post_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('fdd_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fdd_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable()->index();
            $table->string('status', 32)->default('pending')->index();
            $table->string('delivery_method', 32)->default('email');
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('legacy_post_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['lead_id', 'fdd_id']);
        });

        Schema::create('document_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->morphs('linkable');
            $table->string('role', 32)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['document_id', 'linkable_type', 'linkable_id']);
        });

        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fdd_delivery_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('signer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable()->index();
            $table->string('status', 32)->default('pending')->index();
            $table->json('signature_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('extras')->nullable();
            $table->unsignedBigInteger('legacy_post_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['lead_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
        Schema::dropIfExists('document_links');
        Schema::dropIfExists('fdd_deliveries');
        Schema::dropIfExists('fdds');
        Schema::dropIfExists('documents');
    }
};
