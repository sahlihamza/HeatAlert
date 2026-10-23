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
        Schema::table('coupures', function (Blueprint $table) {
            // Référence unique STEG (RSS-{hash} ou HTML-{hash} ou DEMO-{date}-{n})
            $table->string('steg_ref', 100)->nullable()->unique()->after('cause')
                  ->comment('Référence unique STEG pour éviter les doublons lors des syncs');

            // Distingue les coupures importées STEG des coupures saisies manuellement
            $table->enum('origin', ['manual', 'steg_rss', 'steg_html', 'steg_demo'])
                  ->default('manual')->after('steg_ref')
                  ->comment('Origine de la coupure : saisie manuelle ou importée depuis STEG');
        });
    }

    public function down(): void
    {
        Schema::table('coupures', function (Blueprint $table) {
            $table->dropUnique(['steg_ref']);
            $table->dropColumn(['steg_ref', 'origin']);
        });
    }
};
