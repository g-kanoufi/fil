<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_forms', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('site_key')->nullable()->index();
            $table->string('entity', 64)->default('lead')->index();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 32)->default('active')->index();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('widget_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('label_override')->nullable();
            $table->string('placeholder')->nullable();
            $table->boolean('required_override')->nullable();
            $table->string('width', 16)->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();

            $table->unique(['widget_form_id', 'field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_form_fields');
        Schema::dropIfExists('widget_forms');
    }
};
