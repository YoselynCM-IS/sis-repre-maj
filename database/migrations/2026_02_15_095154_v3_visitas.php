<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up(): void
    // {
    //     // 1. Añadimos el contador de modificaciones a la tabla principal
    //     Schema::table('visitas', function (Blueprint $table) {
    //         $table->integer('modificaciones_realizadas')->default(0)->after('es_primera_visita');
    //     });

    //     // 2. Creamos la tabla de logs para auditoría de cambios
    //     Schema::create('visita_logs', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('visita_id')->constrained('visitas')->onDelete('cascade');
    //         $table->foreignId('user_id')->constrained('users')->comment('Usuario que editó');
    //         $table->json('snapshot_anterior')->comment('Datos antes del cambio');
    //         $table->string('motivo_cambio')->comment('Justificación obligatoria');
    //         $table->timestamps();
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::dropIfExists('visita_logs');
    //     Schema::table('visitas', function (Blueprint $table) {
    //         $table->dropColumn('modificaciones_realizadas');
    //     });
    // }
};