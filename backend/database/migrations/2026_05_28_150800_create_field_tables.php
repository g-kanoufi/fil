<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_groups', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->string('slug')->nullable()->unique();
            $table->json('location_rules')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 32)->default('active')->index();
            $table->string('legacy_group_key')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_group_id')->constrained()->cascadeOnDelete();
            $table->string('key')->index();
            $table->string('name');
            $table->string('type', 64)->index();
            $table->json('config')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('required')->default(false);
            $table->string('status', 32)->default('active')->index();
            $table->string('legacy_field_key')->nullable();
            $table->timestamps();

            $table->unique(['field_group_id', 'key']);
        });

        Schema::create('field_role_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained()->cascadeOnDelete();
            $table->string('role')->index();
            $table->string('permission', 32)->default('read');
            $table->json('conditions')->nullable();
            $table->timestamps();

            $table->unique(['field_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_role_rules');
        Schema::dropIfExists('fields');
        Schema::dropIfExists('field_groups');
    }
};
