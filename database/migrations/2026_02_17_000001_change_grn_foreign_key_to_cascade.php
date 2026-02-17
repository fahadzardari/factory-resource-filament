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
        Schema::table('inventory_transactions', function (Blueprint $table) {
            // Drop old restrict constraint
            $table->dropForeign(['grn_id']);
            
            // Add new cascade constraint
            $table->foreign('grn_id')->references('id')->on('goods_receipt_notes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            // Restore old restrict constraint
            $table->dropForeign(['grn_id']);
            $table->foreign('grn_id')->references('id')->on('goods_receipt_notes')->onDelete('restrict');
        });
    }
};
