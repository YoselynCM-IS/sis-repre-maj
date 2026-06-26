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
        Schema::table('cobranzas', function (Blueprint $table) {
            // Se crea la columna ENUM con las tres opciones solicitadas después de metodo_pago
            $table->enum('tipo_pago', ['cie', 'venta directa', 'escuela'])
                  ->nullable() // Se puede definir como nullable o asignarle un ->default('cie') si lo prefieres
                  ->after('metodo_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cobranzas', function (Blueprint $table) {
            // Se remueve la columna si se revierte la migración
            $table->dropColumn('tipo_pago');
        });
    }
};