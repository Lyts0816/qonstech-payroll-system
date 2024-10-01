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
        Schema::create('earnings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EmployeeID'); 
            $table->unsignedBigInteger('OvertimeID'); 
            $table->decimal('Holiday', 10, 2); 
            $table->decimal('Leave', 10, 2);
            $table->decimal('Total', 10, 2);
            $table->varchar('EarningType' , 255); 
            $table->decimal('Amount', 15, 2);
            $table->timestamps();


            $table->foreign('EmployeeID')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('OvertimeID')->references('id')->on('overtime')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('earnings');
    }
};
