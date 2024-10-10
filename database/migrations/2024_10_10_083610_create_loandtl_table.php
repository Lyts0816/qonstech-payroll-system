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
        Schema::create('loandtl', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('loanid')->nullable();
            $table->date('sequence')->nullable();
            $table->date('tran_date')->nullable();
            $table->bigInteger('PeriodID')->nullable();
            $table->decimal('Amount', 15, 2)->nullable();
            $table->tinyInteger('IsPaid')->nullable();
            $table->tinyInteger('IsRenewed')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loandtl');
    }
};
