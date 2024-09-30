<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->unsignedBigInteger('overtime_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('schedule_id')->nullable();


            $table->string('first_name', 50);
            $table->string('middle_name', 50)->nullable();
            $table->string('last_name', 50);
            $table->string('employment_type', 50);
            $table->string('street', 50);
            $table->string('barangay', 50);
            $table->string('city', 50);
            $table->string('province', 50);
            $table->string('contact_number', 15);
            $table->string('status');
            $table->string('attendance_code', 50);
            $table->string('TaxIdentificationNumber', 50)->nullable();
            $table->string('PhilHealthNumber', 50)->nullable();
            $table->string('PagibigNumber', 50)->nullable();


            $table->foreign('position_id')->references('id')->on('positions')->onDelete('cascade');
            $table->foreign('overtime_id')->references('id')->on('overtime')->onDelete('cascade'); // New foreign key
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('schedule_id')->references('id')->on('worksched')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
