<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cobranzas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id')->unique(); // Vinculado de manera única por cliente maestro
            $table->enum('metodo_pago', ['Pago de CIE', 'Venta directa', 'Escuela']);
            
            // Campos condicionales obligatorios para "Escuela"
            $table->string('responsable')->nullable();
            $table->string('telefono')->nullable();
            $table->string('correo')->nullable();
            
            $table->timestamps();

            // Llave foránea para mantener integridad de datos
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cobranzas');
    }
};