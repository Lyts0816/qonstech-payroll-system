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
        Schema::create('pagibig', function (Blueprint $table) {
            $table->id();
            $table->decimal('MonthlySalary', 15, 2)->nullable();
            $table->decimal('Rate', 15, 2)->nullable();

            $table->decimal('MinimumSalary', 15, 2)->nullable();
            $table->decimal('MaximumSalary', 15, 2)->nullable();

            $table->decimal('EmployeeRate', 15, 2)->nullable();
            $table->decimal('EmployerRate', 15, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagibig');
    }
};
