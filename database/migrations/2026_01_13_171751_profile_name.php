<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up()
    // {
    //     Schema::table('users', function (Blueprint $table) {
    //         // NUEVO: Campo para el nombre real separado del nombre de usuario
    //         $table->string('full_name')->nullable()->after('name');
            
    //     }
    //     );
    // }

    // public function down()
    // {
    //     Schema::dropIfExists('delegates');
    //     Schema::table('users', function (Blueprint $table) {
    //         $table->dropColumn(['full_name', 'rfc', 'phone', 'personal_phone', 'position', 'state_id', 'city', 'address', 'employee_id']);
    //     });
    // }
};