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
        Schema::table('series', function (Blueprint $table) {
            // Se agrega short_name de tipo string, permitiendo nulos, justo después de 'serie'
            $table->string('short_name')
                  ->nullable()
                  ->after('serie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            // En caso de revertir la migración, se elimina la columna
            $table->dropColumn('short_name');
        });
    }
};