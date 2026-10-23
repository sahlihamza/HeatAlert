<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipements_sensibles', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->string('type', 100);
            $table->text('description')->nullable();
            $table->string('adresse')->nullable();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->string('niveau_sensibilite', 20)->default('moyen');
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->index(['actif', 'niveau_sensibilite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipements_sensibles');
    }
};
