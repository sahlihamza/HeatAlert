<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prediction_coupures', function (Blueprint $table) {
            $table->dateTime('estimated_outage_start')->nullable()->after('target_window_end');
            $table->dateTime('estimated_outage_end')->nullable()->after('estimated_outage_start');
            $table->string('estimated_outage_cause', 100)->nullable()->after('estimated_outage_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prediction_coupures', function (Blueprint $table) {
            $table->dropColumn(['estimated_outage_start', 'estimated_outage_end', 'estimated_outage_cause']);
        });
    }
};
