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
        Schema::table('goods_receipt_notes', function (Blueprint $table) {
            // Drop the old resource-related columns that were moved to line items
            // Only if fresh migration (no existing data to migrate)
            if (Schema::hasColumn('goods_receipt_notes', 'resource_id')) {
                $table->dropForeign(['resource_id']);
                $table->dropColumn('resource_id');
            }
            
            if (Schema::hasColumn('goods_receipt_notes', 'quantity_received')) {
                $table->dropColumn('quantity_received');
            }
            
            if (Schema::hasColumn('goods_receipt_notes', 'unit_price')) {
                $table->dropColumn('unit_price');
            }
            
            if (Schema::hasColumn('goods_receipt_notes', 'total_value')) {
                $table->dropColumn('total_value');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipt_notes', function (Blueprint $table) {
            // Re-add columns only if they don't exist
            if (!Schema::hasColumn('goods_receipt_notes', 'resource_id')) {
                $table->foreignId('resource_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('resources')
                    ->onDelete('restrict');
            }

            if (!Schema::hasColumn('goods_receipt_notes', 'quantity_received')) {
                $table->decimal('quantity_received', 15, 3)
                    ->nullable()
                    ->after('resource_id');
            }

            if (!Schema::hasColumn('goods_receipt_notes', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)
                    ->nullable()
                    ->after('quantity_received');
            }

            if (!Schema::hasColumn('goods_receipt_notes', 'total_value')) {
                $table->decimal('total_value', 15, 2)
                    ->nullable()
                    ->after('unit_price');
            }
        });
    }
};
