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
        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id();
            
            // Conversion relationship
            $table->foreignId('from_unit_id')
                ->constrained('units')
                ->onDelete('cascade');
            
            $table->foreignId('to_unit_id')
                ->constrained('units')
                ->onDelete('cascade');
            
            // Conversion factor
            $table->decimal('conversion_factor', 20, 10);
            // Example: 1 kg = 1000 g, so from_unit=kg, to_unit=g, factor=1000
            
            // Audit
            $table->timestamps();
            
            // Ensure no duplicate conversions
            $table->unique(['from_unit_id', 'to_unit_id']);
            
            // Indexes for faster queries
            $table->index('from_unit_id');
            $table->index('to_unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_conversions');
    }
};
