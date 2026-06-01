<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interest_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('interest_regions')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 8)->nullable();
            $table->string('slug');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 32)->default('active')->index();
            $table->unsignedBigInteger('legacy_term_id')->nullable()->unique();
            $table->timestamps();

            $table->unique(['parent_id', 'slug']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('interest_region_id')
                ->nullable()
                ->after('area_id')
                ->constrained('interest_regions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('interest_region_id');
        });

        Schema::dropIfExists('interest_regions');
    }
};
