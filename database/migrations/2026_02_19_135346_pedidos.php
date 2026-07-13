<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade los campos de totales y estados de inventario a la tabla pedidos
     * para sincronizarla con el flujo de almacén solicitado.
     */
    // public function up(): void
    // {
    //     Schema::table('pedidos', function (Blueprint $table) {
    //         // Totales de Inventario
    //         if (!Schema::hasColumn('pedidos', 'total_quantity')) {
    //             $table->integer('total_quantity')->default(0)->after('numero_referencia')->comment('Unidades totales del pedido');
    //         }
    //         if (!Schema::hasColumn('pedidos', 'total')) {
    //             $table->double('total', 10, 2)->default(0.00)->after('total_quantity')->comment('Monto total del pedido');
    //         }
    //         if (!Schema::hasColumn('pedidos', 'total_solicitar')) {
    //             $table->integer('total_solicitar')->default(0)->after('total');
    //         }

    //         // Estados y Auditoría de Almacén
    //         if (!Schema::hasColumn('pedidos', 'estado')) {
    //             $table->enum('estado', ['proceso', 'cancelado', 'en orden', 'de inventario'])
    //                   ->default('proceso')
    //                   ->after('status');
    //         }
    //         if (!Schema::hasColumn('pedidos', 'actualizado_por')) {
    //             $table->string('actualizado_por')->nullable()->after('estado');
    //         }
    //         if (!Schema::hasColumn('pedidos', 'cerrado_por')) {
    //             $table->string('cerrado_por', 50)->nullable()->after('actualizado_por');
    //         }
    //     });
    // }

    // /**
    //  * Revierte los cambios.
    //  */
    // public function down(): void
    // {
    //     Schema::table('pedidos', function (Blueprint $table) {
    //         $table->dropColumn([
    //             'total_quantity', 
    //             'total', 
    //             'total_solicitar', 
    //             'estado', 
    //             'actualizado_por', 
    //             'cerrado_por'
    //         ]);
    //     });
    // }
};