<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up(): void
    // {
    //     Schema::create('password_tickets', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); 
            
    //         $table->string('username_provided'); 
    //         $table->string('email_provided');    
    //         $table->string('status')->default('pending'); 
            
    //         $table->timestamps();
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::dropIfExists('password_tickets');
    // }
};
