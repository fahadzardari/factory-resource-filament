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
        Schema::create('goods_receipt_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number')->unique()->index(); // Format: GRN-2026-001
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->foreignId('resource_id')->constrained('resources')->onDelete('restrict');
            $table->decimal('quantity_received', 15, 3); // How much actually arrived
            $table->decimal('unit_price', 10, 2); // Cost per unit
            $table->decimal('total_value', 15, 2); // Calculated: quantity × price
            $table->string('delivery_reference')->nullable(); // Tracking/Shipment number
            $table->date('receipt_date')->index(); // When goods arrived
            $table->text('notes')->nullable(); // Any remarks (damaged, discrepancy, etc.)
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_notes');
    }
};
