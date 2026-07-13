<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up(): void
    // {
    //     Schema::table('gastos', function (Blueprint $table) {
    //         // Guardaremos el nombre del estado para referencia rápida
    //         $table->string('estado_nombre')->nullable()->after('fecha');
            
    //         // Columna CRÍTICA: Aquí guardaremos el JSON con el desglose de sub-gastos
    //         // [ { "concepto": "...", "monto": 100, "es_facturado": true }, ... ]
    //         $table->json('detalles')->nullable()->after('monto');
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::table('gastos', function (Blueprint $table) {
    //         $table->dropColumn(['detalles', 'estado_nombre']);
    //     });
    // }
};