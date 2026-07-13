<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up(): void
    // {
    //     Schema::create('pedido_detalles', function (Blueprint $table) {
    //         $table->id();

    //         $table->foreignId('pedido_id')->constrained('pedidos')->onDelete('cascade');

    //         $table->unsignedBigInteger('libro_id');

    //         $table->string('tipo_licencia', 100);
    //         $table->integer('cantidad');

    //         $table->decimal('precio_unitario', 10, 2);
    //         $table->decimal('costo_total', 10, 2);

    //         $table->timestamps();
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::dropIfExists('pedido_detalles');
    // }
};