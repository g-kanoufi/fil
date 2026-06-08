<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->string('legacy_post_type', 64)->default('')->after('entity');
            $table->dropUnique(['field_group_id', 'key']);
            $table->unique(['field_group_id', 'key', 'legacy_post_type'], 'fields_group_key_post_type_unique');
            $table->index(['entity', 'legacy_post_type'], 'fields_entity_post_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->dropIndex('fields_entity_post_type_idx');
            $table->dropUnique('fields_group_key_post_type_unique');
            $table->unique(['field_group_id', 'key']);
            $table->dropColumn('legacy_post_type');
        });
    }
};
