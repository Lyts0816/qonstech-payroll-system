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
        Schema::create('worksched', function (Blueprint $table) {
            $table->id();
            $table->string('ScheduleName', 50);
            $table->string('ScheduleType', 50);
            
            $table->boolean('monday')->default(false);
            $table->boolean('tuesday')->default(false);
            $table->boolean('wednesday')->default(false);
            $table->boolean('thursday')->default(false);
            $table->boolean('friday')->default(false);
            $table->boolean('saturday')->default(false);
            $table->boolean('sunday')->default(false);


            $table->decimal('RegularHours', 5, 2);
            $table->time('CheckinOne');
            $table->time('CheckoutOne');
            $table->time('CheckinTwo');
            $table->time('CheckoutTwo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worksched');
    }
};
