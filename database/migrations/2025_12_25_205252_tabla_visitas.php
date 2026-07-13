<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up(): void
    // {
    //     Schema::create('visitas', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('user_id')->constrained('users'); 
    //         $table->foreignId('cliente_id')->constrained('clientes'); 
            
    //         $table->date('fecha');
    //         $table->string('persona_entrevistada');
    //         $table->string('cargo');
    //         $table->text('libros_interes')->nullable(); 
    //         $table->boolean('material_entregado')->default(false);
    //         $table->text('comentarios')->nullable();
    //         $table->date('proxima_visita_estimada')->nullable();
            
    //         $table->boolean('es_primera_visita')->default(false);
            
    //         $table->timestamps();
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::dropIfExists('visitas');
    // }
};