<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    // public function up(): void
    // {
    //     Schema::create('comprobantes', function (Blueprint $table) {
    //         $table->id();
            
    //         $table->foreignId('gasto_id')->constrained('gastos')->onDelete('cascade');
            
    //         $table->string('name')->comment('Nombre del archivo en el servidor (YYMMDD-H:M:S_id-[gasto_id].extension).');
    //         $table->unsignedInteger('size')->comment('Peso del archivo en bytes.'); 
    //         $table->string('extension', 10);
    //         $table->text('public_url')->comment('URL pública para visualización desde Dropbox.');
            
    //         $table->timestamps();
    //     });
    // }

 
    // public function down(): void
    // {
    //     Schema::dropIfExists('comprobantes');
    // }
};