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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            
            // Unit definition
            $table->string('name');              // e.g., "Kilogram"
            $table->string('code')->unique();   // e.g., "kg"
            $table->string('unit_type');        // e.g., "weight", "length", "volume", "count"
            $table->boolean('is_base_unit')->default(false);  // TRUE if base unit for its type
            $table->text('description')->nullable();
            
            // Audit
            $table->timestamps();
            
            // Index for faster queries
            $table->index('unit_type');
            $table->index('is_base_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
