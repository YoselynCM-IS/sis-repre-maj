<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración para añadir los campos de identificación del plantel 
     * directamente a la tabla de visitas.
     */
    // public function up(): void
    // {
    //     Schema::table('visitas', function (Blueprint $table) {
    //         // Añadimos los campos solo si no existen para evitar errores de duplicidad
    //         if (!Schema::hasColumn('visitas', 'nombre_plantel')) {
    //             $table->string('nombre_plantel')->nullable()->after('cliente_id');
    //         }
    //         if (!Schema::hasColumn('visitas', 'nivel_educativo_plantel')) {
    //             $table->string('nivel_educativo_plantel')->nullable()->after('nombre_plantel');
    //         }
    //         if (!Schema::hasColumn('visitas', 'direccion_plantel')) {
    //             $table->text('direccion_plantel')->nullable()->after('nivel_educativo_plantel');
    //         }
    //         if (!Schema::hasColumn('visitas', 'estado_id')) {
    //             $table->foreignId('estado_id')->nullable()->after('direccion_plantel')->constrained('estados');
    //         }
    //         if (!Schema::hasColumn('visitas', 'latitud')) {
    //             $table->decimal('latitud', 10, 8)->nullable()->after('estado_id');
    //         }
    //         if (!Schema::hasColumn('visitas', 'longitud')) {
    //             $table->decimal('longitud', 11, 8)->nullable()->after('latitud');
    //         }
    //         if (!Schema::hasColumn('visitas', 'telefono_plantel')) {
    //             $table->string('telefono_plantel')->nullable()->after('longitud');
    //         }
    //         if (!Schema::hasColumn('visitas', 'email_plantel')) {
    //             $table->string('email_plantel')->nullable()->after('telefono_plantel');
    //         }
    //         if (!Schema::hasColumn('visitas', 'director_plantel')) {
    //             $table->string('director_plantel')->nullable()->after('email_plantel');
    //         }
    //     });
    // }

    // /**
    //  * Revierte la migración eliminando las columnas añadidas.
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
    //             'director_plantel'
    //         ]);
    //     });
    // }
};