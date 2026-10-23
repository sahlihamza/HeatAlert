<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module4_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipement_sensible_id')->nullable()->constrained('equipements_sensibles')->nullOnDelete();
            $table->text('message');
            $table->string('canal', 20)->default('app');
            $table->timestamp('date_envoi')->useCurrent();
            $table->boolean('lue')->default(false);
            $table->string('deduplication_key', 191)->unique();
            $table->timestamps();
            $table->index(['user_id', 'lue']);
            $table->index(['canal', 'date_envoi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module4_notifications');
    }
};
