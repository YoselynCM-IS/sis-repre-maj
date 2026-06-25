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
        Schema::create('usos_cfdi', function (Blueprint $table) {
            $table->id();
            
            // Columnas solicitadas con tipos de datos óptimos para catálogos SAT
            $table->string('c_UsoCFDI', 10)->unique(); // Código del uso (Ej: G01, P01, D01)
            $table->string('descripcion', 150);       // Descripción del uso del CFDI
            
            // Flags booleanos para identificar a quién aplica de forma rápida
            $table->boolean('persona_fisica')->default(true);
            $table->boolean('persona_moral')->default(true);
            
            // Regímenes válidos para este uso (se guarda comúnmente como texto separado por comas o JSON)
            $table->text('regimen_fiscal_receptor')->nullable(); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usos_cfdi');
    }
};