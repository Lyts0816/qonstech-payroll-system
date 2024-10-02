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
        Schema::create('loan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EmployeeID');
            $table->string('LoanType', 15);
            $table->decimal('LoanAmount', 15, 2);
            $table->decimal('Balance', 15, 2);
            $table->decimal('MonthlyDeduction', 15, 2);
            $table->integer('NumberOfPayments ');
            $table->decimal('WeeklyDeduction ', 15, 2);
            $table->date('StartDate');
            $table->date('EndDate');

            $table->foreign('EmployeeID')->references('id')->on('employees')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan');
    }
};
