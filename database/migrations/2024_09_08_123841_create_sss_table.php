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
        Schema::create('sss', function (Blueprint $table) {
            $table->id();
            $table->decimal('MinSalary', 15, 2)->nullable();
            $table->decimal('MaxSalary', 15, 2)->nullable();
            $table->decimal('EmployeeShare', 15, 2)->nullable();
            $table->decimal('EmployerShare',15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sss');
    }
};
