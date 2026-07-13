<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla para auditar los cambios en los paquetes de gastos.
     */
    // public function up(): void
    // {
    //     Schema::create('gasto_logs', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('gasto_id')->constrained('gastos')->onDelete('cascade');
    //         $table->foreignId('user_id')->constrained('users')->comment('Usuario que realizó el cambio');
            
    //         // Guardamos el estado completo de los conceptos antes de la edición
    //         $table->json('snapshot_anterior')->comment('Detalles del gasto antes de ser modificados o eliminados');
            
    //         $table->string('motivo_cambio')->nullable()->comment('Explicación breve del ajuste');
    //         $table->timestamps();
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::dropIfExists('gasto_logs');
    // }
};