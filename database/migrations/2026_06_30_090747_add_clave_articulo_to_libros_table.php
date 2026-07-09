<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('libros', function (Blueprint $blueprint) {
            // Se agrega la columna clave_articulo de tipo string, permitiendo nulos (opcional) después de serie_id
            $blueprint->string('clave_articulo')
                      ->nullable()
                      ->after('serie_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('libros', function (Blueprint $blueprint) {
            // En caso de hacer un rollback, eliminamos la columna
            $blueprint->dropColumn('clave_articulo');
        });
    }
};