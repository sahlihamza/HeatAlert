<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('equipement_sensible_predictions')) {
            Schema::create('equipement_sensible_predictions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('equipement_sensible_id')
                    ->constrained('equipements_sensibles')
                    ->cascadeOnDelete();
                $table->decimal('probability', 5, 4);
                $table->string('risk_level', 20);
                $table->unsignedSmallInteger('target_window_hours')->default(24);
                $table->dateTime('target_window_start');
                $table->dateTime('target_window_end_exclusive');
                $table->string('model_version', 255);
                $table->json('factors');
                $table->json('warnings')->nullable();
                $table->json('input_snapshot');
                $table->timestamps();
            });
        }

        $indexNames = array_column(Schema::getIndexes('equipement_sensible_predictions'), 'name');
        if (!in_array('equip_risk_window_unique', $indexNames, true)) {
            Schema::table('equipement_sensible_predictions', function (Blueprint $table) {
                $table->unique(
                    ['equipement_sensible_id', 'target_window_start'],
                    'equip_risk_window_unique'
                );
            });
        }

        if (!in_array('equip_risk_level_window_index', $indexNames, true)) {
            Schema::table('equipement_sensible_predictions', function (Blueprint $table) {
                $table->index(
                    ['risk_level', 'target_window_start'],
                    'equip_risk_level_window_index'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('equipement_sensible_predictions');
    }
};
