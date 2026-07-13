<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade los campos fiscales y de dirección desglosada (Dipomex) a la tabla clientes.
     */
    // public function up(): void
    // {
    //     Schema::table('clientes', function (Blueprint $table) {
    //         // Campos Fiscales
    //         if (!Schema::hasColumn('clientes', 'regimen_fiscal')) {
    //             $table->string('regimen_fiscal', 10)->nullable()->after('rfc');
    //         }

    //         // Dirección Desglosada (Dipomex)
    //         if (!Schema::hasColumn('clientes', 'cp')) {
    //             $table->string('cp', 5)->nullable()->after('direccion');
    //         }
    //         if (!Schema::hasColumn('clientes', 'municipio')) {
    //             $table->string('municipio')->nullable()->after('cp');
    //         }
    //         if (!Schema::hasColumn('clientes', 'colonia')) {
    //             $table->string('colonia')->nullable()->after('municipio');
    //         }
    //         if (!Schema::hasColumn('clientes', 'calle_num')) {
    //             $table->string('calle_num')->nullable()->after('colonia');
    //         }
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::table('clientes', function (Blueprint $table) {
    //         $table->dropColumn(['regimen_fiscal', 'cp', 'municipio', 'colonia', 'calle_num']);
    //     });
    // }
};