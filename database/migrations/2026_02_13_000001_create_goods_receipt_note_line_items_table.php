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
        Schema::create('goods_receipt_note_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('goods_receipt_notes')->onDelete('cascade');
            $table->foreignId('resource_id')->constrained('resources')->onDelete('restrict');
            $table->decimal('quantity_received', 15, 3); // Quantity in receipt unit
            $table->string('receipt_unit'); // Unit of receipt (e.g., kg, liter, pieces, etc.)
            $table->decimal('unit_price', 10, 2); // Price per receipt unit
            $table->decimal('total_value', 15, 2); // Calculated: quantity × price
            $table->timestamps();

            // Indexes for performance
            $table->index('grn_id');
            $table->index('resource_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_note_line_items');
    }
};
