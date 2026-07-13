<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración para añadir el control de dueño (user_id) 
     * a los receptores de pedidos.
     */
    // public function up(): void
    // {
    //     Schema::table('pedido_receptores', function (Blueprint $table) {
    //         // Añadimos el user_id para identificar al dueño del registro
    //         // Se coloca después de cliente_id para mantener orden técnico
    //         if (!Schema::hasColumn('pedido_receptores', 'user_id')) {
    //             $table->foreignId('user_id')
    //                   ->nullable()
    //                   ->after('cliente_id')
    //                   ->constrained('users')
    //                   ->onDelete('set null')
    //                   ->comment('Representante dueño del receptor');
    //         }

    //         // Aseguramos un índice en el RFC para búsquedas rápidas de duplicidad global
    //         $table->index('rfc');
    //     });
    // }

    // /**
    //  * Revierte los cambios.
    //  */
    // public function down(): void
    // {
    //     Schema::table('pedido_receptores', function (Blueprint $table) {
    //         $table->dropForeign(['user_id']);
    //         $table->dropColumn('user_id');
    //     });
    // }
};