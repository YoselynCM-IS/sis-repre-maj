<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up()
    // {
    //     // 1. LIMPIEZA DE LLAVES FORÁNEAS (Evita errores 121 y 1553)
    //     Schema::table('visitas', function (Blueprint $table) {
    //         // Eliminamos la foránea de estado_id antes de intentar borrar la columna
    //         if (Schema::hasColumn('visitas', 'estado_id')) {
    //             // Usamos un try-catch por si la llave tiene un nombre distinto en tu DB
    //             try {
    //                 $table->dropForeign(['estado_id']);
    //             } catch (\Exception $e) {
    //                 // Si falla por nombre, intentamos con el nombre estándar de Laravel
    //                 try { $table->dropForeign('visitas_estado_id_foreign'); } catch (\Exception $ex) {}
    //             }
    //         }

    //         // Eliminamos preventivamente la de cliente_id por si quedó huérfana de intentos fallidos
    //         try {
    //             $table->dropForeign(['cliente_id']);
    //         } catch (\Exception $e) {}
    //     });

    //     // 2. APLICAR CAMBIOS ESTRUCTURALES
    //     Schema::table('visitas', function (Blueprint $table) {
    //         // Crear la relación foránea sobre la columna cliente_id existente
    //         $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');

    //         // 3. Eliminar columnas redundantes
    //         $columnsToDrop = [
    //             'nombre_plantel',
    //             'nivel_educativo_plantel',
    //             'direccion_plantel',
    //             'estado_id', // Ahora que quitamos la foránea arriba, ya se puede borrar
    //             'latitud',
    //             'longitud',
    //             'telefono_plantel',
    //             'email_plantel',
    //             'director_plantel'
    //         ];

    //         foreach ($columnsToDrop as $column) {
    //             if (Schema::hasColumn('visitas', $column)) {
    //                 $table->dropColumn($column);
    //             }
    //         }
    //     });
    // }

    // public function down()
    // {
    //     Schema::table('visitas', function (Blueprint $table) {
    //         $table->dropForeign(['cliente_id']);
    //     });
    // }
};