<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_relation_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 64);
            $table->unsignedBigInteger('entity_id');
            $table->string('related_type', 64);
            $table->unsignedBigInteger('related_id');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['entity_type', 'entity_id', 'field_id'], 'frl_entity_field_idx');
            $table->index(['field_id', 'related_type', 'related_id'], 'frl_field_related_idx');
            $table->unique(
                ['field_id', 'entity_type', 'entity_id', 'related_type', 'related_id'],
                'frl_unique_link',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_relation_links');
    }
};
