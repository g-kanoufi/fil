<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_field_id')->default(0)->after('field_group_id');
            $table->index('parent_field_id');
            $table->dropUnique('fields_group_key_post_type_unique');
            $table->unique(
                ['field_group_id', 'key', 'legacy_post_type', 'parent_field_id'],
                'fields_group_key_post_type_parent_unique',
            );
        });

        Schema::create('field_repeater_rows', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 64)->index();
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('field_id')->constrained('fields')->cascadeOnDelete();
            $table->unsignedInteger('row_index');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['entity_type', 'entity_id', 'field_id', 'row_index'], 'repeater_rows_entity_field_index_unique');
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('field_repeater_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_repeater_row_id')->constrained('field_repeater_rows')->cascadeOnDelete();
            $table->foreignId('sub_field_id')->constrained('fields')->cascadeOnDelete();
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 20, 6)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->date('value_date')->nullable();
            $table->timestamp('value_datetime')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['field_repeater_row_id', 'sub_field_id'], 'repeater_values_row_subfield_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_repeater_values');
        Schema::dropIfExists('field_repeater_rows');

        Schema::table('fields', function (Blueprint $table) {
            $table->dropUnique('fields_group_key_post_type_parent_unique');
            $table->unique(['field_group_id', 'key', 'legacy_post_type'], 'fields_group_key_post_type_unique');
            $table->dropIndex(['parent_field_id']);
            $table->dropColumn('parent_field_id');
        });
    }
};
