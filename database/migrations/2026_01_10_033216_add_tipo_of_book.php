<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     Schema::table('pedido_detalles', function (Blueprint $blueprint) {
    //         // 1. Borramos el campo anterior si existía uno que definiera el tipo de pedido de forma errónea
    //         // O si simplemente quieres limpiar la estructura previa.
    //         // $blueprint->dropColumn('campo_anterior'); 

    //         // 2. Agregamos el campo 'tipo' para identificar cada material individualmente
    //         // Usamos enum para asegurar que solo acepte 'venta' o 'promocion'
    //         $blueprint->enum('tipo', ['venta', 'promocion'])
    //                   ->default('venta')
    //                   ->after('libro_id');
    //     });

    //     // Opcional: Si el campo 'tipo_pedido' estaba en la tabla 'pedidos' y ya no es necesario
    //     // porque ahora se define por ítem, puedes borrarlo de la tabla principal:
    //     /*
    //     Schema::table('pedidos', function (Blueprint $blueprint) {
    //         $blueprint->dropColumn('tipo_pedido');
    //     });
    //     */
    // }

    // /**
    //  * Reverse the migrations.
    //  */
    // public function down(): void
    // {
    //     Schema::table('pedido_detalles', function (Blueprint $blueprint) {
    //         $blueprint->dropColumn('tipo');
    //     });
    // }
};