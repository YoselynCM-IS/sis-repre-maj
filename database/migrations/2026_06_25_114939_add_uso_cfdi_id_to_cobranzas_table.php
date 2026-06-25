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
        Schema::table('cobranzas', function (Blueprint $table) {
            // Se agrega la columna justo después de regimen_fiscal_id como llave foránea
            $table->foreignId('uso_cfdi_id')
                  ->nullable()
                  ->after('regimen_fiscal_id')
                  ->constrained('usos_cfdi')
                  ->onDelete('set null'); // Mantiene la integridad si se borrara un uso
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cobranzas', function (Blueprint $table) {
            // Primero eliminamos la restricción de llave foránea y luego la columna
            $table->dropForeign(['uso_cfdi_id']);
            $table->dropColumn('uso_cfdi_id');
        });
    }
};