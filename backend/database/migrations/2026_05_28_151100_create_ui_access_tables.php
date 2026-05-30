<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('ui_menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 64)->index();
            $table->string('key')->index();
            $table->string('label')->nullable();
            $table->string('parent_key')->nullable()->index();
            $table->string('item_type', 32)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('legacy_adminimize_key')->nullable();
            $table->timestamps();

            $table->unique(['domain', 'key']);
        });

        Schema::create('role_ui_grants', function (Blueprint $table) {
            $table->id();
            $table->string('role')->index();
            $table->foreignId('ui_menu_item_id')->constrained()->cascadeOnDelete();
            $table->boolean('allowed')->default(true);
            $table->timestamps();

            $table->unique(['role', 'ui_menu_item_id']);
        });

        Schema::create('role_note_grants', function (Blueprint $table) {
            $table->id();
            $table->string('role')->index();
            $table->string('entity', 64)->index();
            $table->boolean('can_view_notes')->default(false);
            $table->boolean('can_view_private_notes')->default(false);
            $table->timestamps();

            $table->unique(['role', 'entity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_note_grants');
        Schema::dropIfExists('role_ui_grants');
        Schema::dropIfExists('ui_menu_items');
        Schema::dropIfExists('client_settings');
    }
};
