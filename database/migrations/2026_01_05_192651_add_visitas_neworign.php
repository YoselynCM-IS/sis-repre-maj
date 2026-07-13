<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para convertir la tabla visitas en el repositorio
     * único de datos del plantel y seguimiento extendido.
     */
    // public function up(): void
    // {
    //     // NO modificamos la tabla 'clientes' ya que la información no se pasará ahí.
        
    //     Schema::table('visitas', function (Blueprint $table) {
    //         // 1. Campos de Identidad y Ubicación del Plantel (Data Persistente en Visitas)
    //         // Se añaden después de cliente_id para mantener un orden lógico
    //         $table->string('nombre_plantel')->nullable()->after('cliente_id');
    //         $table->string('nivel_educativo_plantel')->nullable()->after('nombre_plantel');
    //         $table->text('direccion_plantel')->nullable()->after('nivel_educativo_plantel');
            
    //         // Relación geográfica opcional para el plantel
    //         $table->foreignId('estado_id')->nullable()->after('direccion_plantel')->constrained('estados');
            
    //         // Coordenadas GPS
    //         $table->decimal('latitud', 10, 8)->nullable()->after('estado_id');
    //         $table->decimal('longitud', 11, 8)->nullable()->after('latitud');
            

            
    //     });
    // }

    // /**
    //  * Revierte las migraciones eliminando las columnas de la bitácora.
    //  */
    // public function down(): void
    // {
    //     Schema::table('visitas', function (Blueprint $table) {
    //         $table->dropForeign(['estado_id']);
            
    //         $table->dropColumn([
    //             'nombre_plantel',
    //             'nivel_educativo_plantel',
    //             'direccion_plantel',
    //             'estado_id',
    //             'latitud',
    //             'longitud',
    //             'telefono_plantel',
    //             'email_plantel',
    //             'director_plantel',
    //             'material_cantidad',
    //             'resultado_visita',
    //             'proxima_accion'
    //         ]);
    //     });
    // }
};