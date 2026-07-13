<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up(): void
    // {
    //     Schema::table('gastos', function (Blueprint $table) {
    //         // Este contador solo aumenta cuando el gasto ya está en FINALIZADO
    //         $table->integer('modificaciones_finalizadas')->default(0)->after('status');
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::table('gastos', function (Blueprint $table) {
    //         $table->dropColumn('modificaciones_finalizadas');
    //     });
    // }
};