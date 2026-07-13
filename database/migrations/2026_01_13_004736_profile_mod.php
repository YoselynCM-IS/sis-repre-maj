<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up()
    // {
    //     Schema::table('users', function (Blueprint $table) {
    //         // Datos Personales y Ubicación
    //         $table->string('rfc', 13)->nullable()->after('email');
    //         $table->string('phone')->nullable()->after('rfc');
    //         $table->string('personal_phone')->nullable()->after('phone');
    //         $table->string('position')->nullable()->after('personal_phone');
    //         $table->foreignId('state_id')->nullable()->after('position');
    //         $table->string('city')->nullable()->after('state_id');
    //         $table->text('address')->nullable()->after('city');
    //         $table->string('employee_id')->nullable()->unique()->after('address');

    //         // Herramientas de Trabajo
    //         $table->string('car_plates')->nullable();
    //         $table->string('tag_number')->nullable();
    //         $table->string('insurance_policy')->nullable();
    //         $table->string('phone_model')->nullable();
    //         $table->string('tablet_model')->nullable();
    //         $table->string('computer_model')->nullable();
    //         $table->string('business_card')->nullable();
    //     });

    //     Schema::create('delegates', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('user_id')->constrained()->onDelete('cascade');
    //         $table->string('name');
    //         $table->string('email');
    //         $table->timestamps();
    //     });
    // }

    // public function down()
    // {
    //     Schema::dropIfExists('delegates');
    //     Schema::table('users', function (Blueprint $table) {
    //         $table->dropColumn([
    //             'rfc', 'phone', 'personal_phone', 'position', 'state_id', 
    //             'city', 'address', 'employee_id', 'car_plates', 'tag_number',
    //             'insurance_policy', 'phone_model', 'tablet_model', 
    //             'computer_model', 'business_card'
    //         ]);
    //     });
    // }
};