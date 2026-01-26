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
        Schema::create('resource_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number')->unique();
            $table->decimal('purchase_price', 10, 2);
            $table->decimal('quantity_purchased', 10, 2);
            $table->decimal('quantity_remaining', 10, 2);
            $table->date('purchase_date');
            $table->string('supplier')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['resource_id', 'purchase_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_batches');
    }
};
