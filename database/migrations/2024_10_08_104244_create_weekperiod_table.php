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
        Schema::create('weekperiod', function (Blueprint $table) {
            $table->id();
            $table->date('StartDate')->nullable();
            $table->date('EndDate')->nullable();
            $table->string('Month', 250)->nullable();
            $table->string('Year', 250)->nullable();
            $table->string('Category', 250)->nullable();
            $table->string('Type', 250)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekperiod');
    }
};
