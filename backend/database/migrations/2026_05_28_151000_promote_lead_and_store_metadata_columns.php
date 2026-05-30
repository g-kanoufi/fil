<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('lead_fdd_status')->nullable()->index()->after('lead_stage');
            $table->string('lead_temp', 32)->nullable()->index()->after('lead_fdd_status');
            $table->string('lead_source', 64)->nullable()->index()->after('lead_temp');
            $table->decimal('likelihood_to_close', 5, 2)->nullable()->index()->after('lead_source');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('form_data');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->string('store_status', 64)->nullable()->index()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('store_status');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->json('form_data')->nullable();
            $table->dropColumn([
                'lead_fdd_status',
                'lead_temp',
                'lead_source',
                'likelihood_to_close',
            ]);
        });
    }
};
