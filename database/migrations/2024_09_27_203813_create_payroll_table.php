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
        Schema::create('payroll', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EmployeeID')->nullable();
            $table->date('PayrollDate')->nullable();
            $table->decimal('TotalEarnings', 10, 2)->nullable();
            $table->decimal('GrossPay', 10, 2)->nullable();
            $table->decimal('TotalDeductions', 10, 2)->nullable();
            $table->decimal('NetPay', 10, 2)->nullable();

            $table->varchar('EmployeeStatus', 100)->nullable();
            $table->varchar('PayrollFrequency', 60)->nullable();
            $table->varchar('PayrollMonth', 30)->nullable();
            $table->varchar('PayrollYear', 255)->nullable();
            $table->varchar('PayrollDate2', 255)->nullable();
            $table->integer('ProjectID')->nullable();

            $table->foreign('EmployeeID')->references('id')->on('employees')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll');
    }
};
