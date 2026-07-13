<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para añadir soporte de GPS y agenda extendida.
     */
    // public function up(): void
    // {
    //     // 1. Añadimos coordenadas al cliente para la ubicación física del plantel
    //     Schema::table('clientes', function (Blueprint $table) {
    //         $table->decimal('latitud', 10, 8)->nullable()->after('direccion');
    //         $table->decimal('longitud', 11, 8)->nullable()->after('latitud');
    //         $table->string('nivel_educativo')->nullable()->after('tipo');
    //     });

    //     // 2. Añadimos campos de material y tipo de acción a la visita
    //     Schema::table('visitas', function (Blueprint $table) {
    //         $table->integer('material_cantidad')->nullable()->after('material_entregado');
    //         // 'proxima_accion' permite diferenciar entre una visita estándar o una presentación
    //         $table->enum('proxima_accion', ['visita', 'presentacion'])->default('visita')->after('proxima_visita_estimada');
    //     });
    // }

    // /**
    //  * Revierte las migraciones.
    //  */
    // public function down(): void
    // {
    //     Schema::table('clientes', function (Blueprint $table) {
    //         $table->dropColumn(['latitud', 'longitud', 'nivel_educativo']);
    //     });

    //     Schema::table('visitas', function (Blueprint $table) {
    //         $table->dropColumn(['material_cantidad', 'proxima_accion']);
    //     });
    // }
};