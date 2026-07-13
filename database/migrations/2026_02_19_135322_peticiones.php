<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla peticiones para el registro detallado de libros o packs 
     * solicitados en un pedido con lógica de inventario.
     */
    // public function up(): void
    // {
    //     Schema::create('peticiones', function (Blueprint $table) {
    //         $table->id();
            
    //         // Relaciones
    //         $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->onDelete('cascade');
    //         $table->unsignedInteger('pack_id')->nullable()->comment('ID de la Serie o Pack');
    //         $table->foreignId('libro_id')->nullable()->constrained('libros');

    //         // Información Técnica
    //         $table->string('tipo', 50)->nullable()->comment('profesor, demo o alumno');
    //         $table->integer('quantity')->default(0);
    //         $table->double('price', 8, 2)->default(0.00);
    //         $table->double('total', 10, 2)->default(0.00);

    //         // Control de Inventario y Almacén
    //         $table->integer('existencia')->default(0);
    //         $table->integer('faltante')->default(0);
    //         $table->integer('solicitar')->default(0);

    //         $table->softDeletes();
    //         $table->timestamps();

    //         // Índices para optimización de búsqueda
    //         $table->index('pedido_id');
    //         $table->index('libro_id');
    //         $table->index('pack_id');
    //     });
    // }

    // /**
    //  * Revierte la migración.
    //  */
    // public function down(): void
    // {
    //     Schema::dropIfExists('peticiones');
    // }
};