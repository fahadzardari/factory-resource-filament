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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            
            // What resource is moving?
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            
            // Where is it? (NULL = Central Hub, ID = Project Site)
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            
            // What type of movement?
            $table->enum('transaction_type', [
                'PURCHASE',          // Stock entering hub from supplier
                'ALLOCATION_OUT',    // Stock leaving hub to project
                'ALLOCATION_IN',     // Stock arriving at project from hub
                'CONSUMPTION',       // Stock consumed/used at project
                'TRANSFER_OUT',      // Stock leaving one project to another
                'TRANSFER_IN',       // Stock arriving from another project
                'ADJUSTMENT',        // Manual correction (if needed)
            ]);
            
            // Quantities and Pricing (always in base unit)
            $table->decimal('quantity', 15, 3); // Can be negative for OUT transactions
            $table->decimal('unit_price', 15, 2); // Price per base unit
            $table->decimal('total_value', 15, 2); // quantity × unit_price
            
            // When did this happen?
            $table->date('transaction_date');
            
            // Reference to related documents (polymorphic)
            $table->string('reference_type')->nullable(); // App\Models\PurchaseOrder, WorkOrder, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            
            // Additional context
            $table->text('notes')->nullable();
            $table->string('supplier')->nullable(); // For PURCHASE transactions
            $table->string('invoice_number')->nullable(); // For PURCHASE transactions
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users');
            
            $table->timestamps();
            
            // Indexes for performance (with custom names to avoid length limit)
            $table->index(['resource_id', 'created_at'], 'idx_resource_created');
            $table->index(['project_id', 'created_at'], 'idx_project_created');
            $table->index(['resource_id', 'project_id', 'transaction_type'], 'idx_res_proj_type');
            $table->index('transaction_date', 'idx_trans_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
