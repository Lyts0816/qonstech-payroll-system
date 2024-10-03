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
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Employee_ID');
            $table->time('Checkin_One')->nullable();
            $table->time('Checkout_One')->nullable();
            $table->time('Checkin_Two')->nullable();
            $table->time('Checkout_Two')->nullable();
            $table->date('Date');
            
            $table->time('overtime_in')->nullable();
            $table->time('overtime_out')->nullable();
            $table->decimal('Total_Hours', 10, 2)->nullable();
            $table->foreign('Employee_ID')->references('id')->on('employees')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
