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
        Schema::create('tax', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID
            $table->decimal('base_rate', 15, 2)->nullable(); // Base tax rate
            $table->decimal('excess_percent', 15, 2)->nullable(); // Excess percentage, if applicable
            $table->decimal('MinSalary', 15, 2)->nullable(); // Minimum salary for the tax bracket
            $table->decimal('MaxSalary', 15, 2)->nullable(); // Maximum salary for the tax bracket
            $table->timestamps(); // Created_at and updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax');
    }
};
