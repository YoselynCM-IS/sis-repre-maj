<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up(): void
    // {
    //     Schema::table('pedidos', function (Blueprint $table) {
    //         // Campos para la nueva lógica de negocio
    //         $table->enum('tipo_pedido', ['normal', 'promocion'])->default('normal')->after('cliente_id');
    //         $table->enum('prioridad', ['baja', 'media', 'alta'])->default('media')->after('tipo_pedido');
    //         $table->string('paqueteria_nombre')->nullable()->after('delivery_option');
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::table('pedidos', function (Blueprint $table) {
    //         $table->dropColumn(['tipo_pedido', 'prioridad', 'paqueteria_nombre']);
    //     });
    // }
};