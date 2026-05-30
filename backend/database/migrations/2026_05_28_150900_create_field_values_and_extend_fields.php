<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->string('entity', 64)->default('lead')->index()->after('field_group_id');
            $table->string('storage', 32)->default('field_value')->index()->after('type');
            $table->string('maps_to_column')->nullable()->after('storage');
            $table->boolean('is_filterable')->default(false)->after('required');
            $table->boolean('is_sortable')->default(false)->after('is_filterable');
            $table->boolean('is_facetable')->default(false)->after('is_sortable');
        });

        Schema::create('field_values', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 64)->index();
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('field_id')->constrained()->cascadeOnDelete();
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 20, 6)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->date('value_date')->nullable();
            $table->timestamp('value_datetime')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['entity_type', 'entity_id', 'field_id']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['field_id', 'value_text']);
            $table->index(['field_id', 'value_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_values');

        Schema::table('fields', function (Blueprint $table) {
            $table->dropColumn([
                'entity',
                'storage',
                'maps_to_column',
                'is_filterable',
                'is_sortable',
                'is_facetable',
            ]);
        });
    }
};
