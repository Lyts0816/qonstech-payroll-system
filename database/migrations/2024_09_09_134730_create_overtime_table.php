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
        Schema::create('overtime', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EmployeeID'); // Add EmployeeID column
            $table->foreign('EmployeeID')->references('id')->on('employees')->onDelete('cascade'); // Set as foreign key

            $table->string('Reason', 50);
            $table->decimal('OvertimeRate', 10, 2)->nullable();
            $table->time('Checkin');
            $table->time('Checkout');
            $table->date('Date');
            $table->string('Status', 15);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime');
    }
};
