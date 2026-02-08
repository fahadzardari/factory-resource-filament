<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum to include new transaction types
        if (DB::getDriverName() === 'mysql') {
            // For MySQL, we need to alter the enum column
            DB::statement("ALTER TABLE inventory_transactions MODIFY transaction_type ENUM(
                'PURCHASE',
                'ALLOCATION_OUT',
                'ALLOCATION_IN',
                'CONSUMPTION',
                'TRANSFER_OUT',
                'TRANSFER_IN',
                'ADJUSTMENT',
                'GOODS_RECEIPT',
                'DIRECT_CONSUMPTION'
            )");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the enum to original values
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inventory_transactions MODIFY transaction_type ENUM(
                'PURCHASE',
                'ALLOCATION_OUT',
                'ALLOCATION_IN',
                'CONSUMPTION',
                'TRANSFER_OUT',
                'TRANSFER_IN',
                'ADJUSTMENT'
            )");
        }
    }
};
