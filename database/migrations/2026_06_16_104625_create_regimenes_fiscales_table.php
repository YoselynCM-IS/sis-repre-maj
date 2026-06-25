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
        Schema::create('regimenes_fiscales', function (Blueprint $table) {
            $table->id();
            // Código numérico o alfanumérico del régimen (ej. "601", "603")
            $table->string('codigo')->unique();
            // Descripción oficial del régimen fiscal
            $table->string('descripcion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regimenes_fiscales');
    }
};