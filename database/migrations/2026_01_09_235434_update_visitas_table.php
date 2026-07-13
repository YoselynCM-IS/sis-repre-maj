<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // public function up()
    // {
    //     // 1. Desactivar restricciones y modo estricto temporalmente para evitar el error 1265
    //     Schema::disableForeignKeyConstraints();
    //     DB::statement('SET SESSION sql_mode = ""');

    //     // 2. Limpieza de llaves con SQL puro
    //     $this->dropForeignKeyIfExists('visitas', 'visitas_estado_id_foreign');
    //     $this->dropForeignKeyIfExists('visitas', 'visitas_cliente_id_foreign');

    //     // 3. Modificaciones de estructura
    //     Schema::table('visitas', function (Blueprint $table) {
    //         // Usamos ->nullable() para evitar el error "Data truncated" si hay filas vacías
    //         $table->unsignedBigInteger('cliente_id')->nullable()->change();
    //     });

    //     Schema::table('visitas', function (Blueprint $table) {
    //         // Ahora creamos la relación
    //         $table->foreign('cliente_id')
    //               ->references('id')
    //               ->on('clientes')
    //               ->onDelete('cascade');

    //         // 4. Eliminación de columnas redundantes
    //         $columnsToDrop = [
    //             'nombre_plantel', 'nivel_educativo_plantel', 'direccion_plantel',
    //             'estado_id', 'latitud', 'longitud', 'telefono_plantel',
    //             'email_plantel', 'director_plantel'
    //         ];

    //         foreach ($columnsToDrop as $column) {
    //             if (Schema::hasColumn('visitas', $column)) {
    //                 $table->dropColumn($column);
    //             }
    //         }
    //     });

    //     Schema::enableForeignKeyConstraints();
    // }

    // private function dropForeignKeyIfExists($table, $keyName)
    // {
    //     $exists = DB::select("
    //         SELECT CONSTRAINT_NAME 
    //         FROM information_schema.TABLE_CONSTRAINTS 
    //         WHERE CONSTRAINT_SCHEMA = DATABASE() 
    //         AND TABLE_NAME = '$table' 
    //         AND CONSTRAINT_NAME = '$keyName' 
    //         AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    //     ");

    //     if (!empty($exists)) {
    //         DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$keyName` ");
    //     }
    // }

    // public function down()
    // {
    //     Schema::table('visitas', function (Blueprint $table) {
    //         if (Schema::hasColumn('visitas', 'cliente_id')) {
    //             $table->dropForeign(['cliente_id']);
    //         }
    //     });
    // }
};