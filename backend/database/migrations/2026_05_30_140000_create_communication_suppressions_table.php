<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 16)->index();
            $table->string('address')->index();
            $table->string('reason', 32)->index();
            $table->string('source', 32)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['channel', 'address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_suppressions');
    }
};
