<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prediction_coupures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones')->restrictOnDelete();
            $table->dateTime('reference_at');
            $table->dateTime('target_window_start');
            $table->dateTime('target_window_end');
            $table->decimal('probability', 8, 7);
            $table->string('risk_level', 20)->nullable();
            $table->boolean('risk_thresholds_defined')->default(false);
            $table->string('model_version', 255);
            $table->json('warnings')->nullable();
            $table->json('input_snapshot')->nullable();
            $table->timestamps();

            $table->index(['zone_id', 'reference_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_coupures');
    }
};
