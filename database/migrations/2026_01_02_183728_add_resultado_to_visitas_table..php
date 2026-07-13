<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up(): void
    // {
    //     Schema::table('visitas', function (Blueprint $table) {
    //         // Añadimos el campo para guardar qué se decidió en la visita
    //         $table->enum('resultado_visita', ['seguimiento', 'compra', 'rechazo'])
    //               ->default('seguimiento')
    //               ->after('es_primera_visita');
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::table('visitas', function (Blueprint $table) {
    //         $table->dropColumn('resultado_visita');
    //     });
    // }
};