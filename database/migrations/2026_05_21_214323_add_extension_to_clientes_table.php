<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Base de datos local/principal
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('extension', 10)->after('tel_oficina'); 
        });

        // // Conexión externa de inventario (Ejecutar solo si se requiere estructuralmente)
        // if (Schema::connection('mysql_inventario')->hasTable('clientes')) {
        //     Schema::connection('mysql_inventario')->table('clientes', function (Blueprint $table) {
        //         $table->string('extension', 10)->after('telefono');
        //     });
        // }
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('extension');
        });

        // if (Schema::connection('mysql_inventario')->hasTable('clientes')) {
        //     Schema::connection('mysql_inventario')->table('clientes', function (Blueprint $table) {
        //         $table->dropColumn('extension');
        //     });
        // }
    }
};