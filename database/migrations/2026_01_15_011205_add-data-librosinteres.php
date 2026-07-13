<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sincroniza la tabla visitas con la nueva estructura de prospectos y materiales JSON.
     */
    // public function up(): void
    // {
    //     // 1. LIMPIEZA PREVENTIVA (Solución al error 4025)
    //     // MariaDB no permite convertir a JSON si el contenido actual no es un JSON válido.
    //     // Forzamos un arreglo vacío [] en registros existentes o nulos.
    //     DB::table('visitas')
    //         ->whereNull('libros_interes')
    //         ->orWhere('libros_interes', '')
    //         ->update(['libros_interes' => '[]']);

    //     Schema::table('visitas', function (Blueprint $table) {
    //         // 2. CONVERSIÓN DE TIPO
    //         $table->json('libros_interes')->nullable()->change();

    //         // 3. ADICIÓN DE COLUMNAS FALTANTES (Snapshot del Plantel)
    //         // Verificamos una por una para no causar errores si alguna ya existiera
    //         if (!Schema::hasColumn('visitas', 'nombre_plantel')) {
    //             $table->string('nombre_plantel')->nullable()->after('cliente_id');
    //         }

    //         if (!Schema::hasColumn('visitas', 'rfc_plantel')) {
    //             $table->string('rfc_plantel', 20)->nullable()->after('nombre_plantel');
    //         }

    //         if (!Schema::hasColumn('visitas', 'direccion_plantel')) {
    //             $table->text('direccion_plantel')->nullable()->after('rfc_plantel');
    //         }

    //         if (!Schema::hasColumn('visitas', 'estado_id')) {
    //             // Relación geográfica para el prospecto
    //             $table->foreignId('estado_id')->nullable()->after('direccion_plantel')->constrained('estados');
    //         }

    //         if (!Schema::hasColumn('visitas', 'latitud')) {
    //             $table->decimal('latitud', 10, 8)->nullable()->after('estado_id');
    //             $table->decimal('longitud', 11, 8)->nullable()->after('latitud');
    //         }

    //         if (!Schema::hasColumn('visitas', 'telefono_plantel')) {
    //             $table->string('telefono_plantel')->nullable()->after('longitud');
    //             $table->string('email_plantel')->nullable()->after('telefono_plantel');
    //             $table->string('director_plantel')->nullable()->after('email_plantel');
    //         }

    //         // Nota: proxima_accion, es_primera_visita y resultado_visita 
    //         // ya existen en tu BD según el dump enviado, por lo que no las tocamos.
    //     });
    // }

    // /**
    //  * Revierte los cambios.
    //  */
    // public function down(): void
    // {
    //     Schema::table('visitas', function (Blueprint $table) {
    //         $table->text('libros_interes')->nullable()->change();
            
    //         // Eliminamos las columnas en caso de rollback
    //         $table->dropForeign(['estado_id']);
    //         $table->dropColumn([
    //             'nombre_plantel',
    //             'rfc_plantel',
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