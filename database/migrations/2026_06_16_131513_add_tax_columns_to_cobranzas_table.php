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
            // Agregamos las nuevas columnas requeridas para facturación
            $table->string('rfc', 13)->nullable()->after('correo');
            $table->text('direccion')->nullable()->after('rfc');
            
            // Relación foránea con la tabla de regimenes_fiscales
            $table->foreignId('regimen_fiscal_id')
                  ->nullable()
                  ->after('direccion')
                  ->constrained('regimenes_fiscales')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cobranzas', function (Blueprint $table) {
            // Eliminamos la llave foránea y las columnas en caso de rollback
            $table->dropForeign(['regimen_fiscal_id']);
            $table->dropColumn(['rfc', 'direccion', 'regimen_fiscal_id']);
        });
    }
};