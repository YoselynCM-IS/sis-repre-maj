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
        Schema::create('status', function (Blueprint $table) {
            $table->id();
            // Relación con el usuario (representante) que hace el cambio
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // Relación con el pedido que se está actualizando
            $table->foreignId('pedido_id')->constrained('pedidos')->onDelete('cascade');
            // Almacena el estado asignado: PROCESO, ENTREGADO o CANCELADO
            $table->string('status');
            // Comentarios o justificación del cambio
            $table->text('comentarios');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status');
    }
};