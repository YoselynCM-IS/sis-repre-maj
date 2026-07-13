<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up(): void
    // {
    //     Schema::create('pedido_logs', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('pedido_id')->constrained('pedidos')->onDelete('cascade');
    //         $table->foreignId('user_id')->constrained('users')->comment('Usuario que realizó el cambio');
            
    //         // Guardamos el estado completo (Cabecera y Detalles) en formato JSON
    //         $table->json('snapshot_anterior')->comment('Datos antes de la modificación');
            
    //         $table->string('motivo_cambio')->nullable();
    //         $table->timestamps();
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::dropIfExists('pedido_logs');
    // }
};