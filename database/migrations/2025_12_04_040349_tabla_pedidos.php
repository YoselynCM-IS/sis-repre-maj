<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up(): void
    // {
    //     Schema::create('pedidos', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('user_id'); 
    //         $table->foreignId('cliente_id'); 
            
    //         $table->unsignedBigInteger('receptor_id')->nullable(); 

    //         $table->enum('receiver_type', ['cliente', 'nuevo']);
    //         $table->enum('delivery_option', ['recoleccion', 'paqueteria', 'none'])->default('none');
    //         $table->text('delivery_address')->nullable(); 
            
    //         $table->text('comments')->nullable(); 
            
    //         $table->enum('status', ['PENDIENTE', 'EN PROCESO', 'ENTREGADO', 'CANCELADO'])->default('PENDIENTE');

    //         $table->timestamps();
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::dropIfExists('pedidos');
    // }
};