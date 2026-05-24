<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delegates', function (Blueprint $table) {
            // Se agrega la columna para enlazar al Representante (tabla users)
            $table->unsignedBigInteger('representative_id')->nullable()->after('user_id');
            $table->foreign('representative_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('delegates', function (Blueprint $table) {
            $table->dropForeign(['representative_id']);
            $table->dropColumn('representative_id');
        });
    }
};
