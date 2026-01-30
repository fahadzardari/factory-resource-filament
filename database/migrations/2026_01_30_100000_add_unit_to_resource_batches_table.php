<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds unit_type to resource_batches to allow multiple units
     * per resource (e.g., timber can be in cubic feet, kg, volume, etc.)
     */
    public function up(): void
    {
        Schema::table('resource_batches', function (Blueprint $table) {
            $table->string('unit_type')->after('batch_number')->default('unit');
            $table->decimal('conversion_factor', 15, 6)->after('unit_type')->default(1.000000);
        });

        // Add check constraints for non-negative quantities (MySQL 8.0.16+)
        // These prevent negative quantities at the database level
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE resources ADD CONSTRAINT chk_resources_total_quantity_non_negative CHECK (total_quantity >= 0)');
            DB::statement('ALTER TABLE resources ADD CONSTRAINT chk_resources_available_quantity_non_negative CHECK (available_quantity >= 0)');
            DB::statement('ALTER TABLE resource_batches ADD CONSTRAINT chk_batches_quantity_purchased_non_negative CHECK (quantity_purchased >= 0)');
            DB::statement('ALTER TABLE resource_batches ADD CONSTRAINT chk_batches_quantity_remaining_non_negative CHECK (quantity_remaining >= 0)');
            DB::statement('ALTER TABLE resource_batches ADD CONSTRAINT chk_batches_remaining_not_exceed_purchased CHECK (quantity_remaining <= quantity_purchased)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove check constraints
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE resources DROP CONSTRAINT IF EXISTS chk_resources_total_quantity_non_negative');
            DB::statement('ALTER TABLE resources DROP CONSTRAINT IF EXISTS chk_resources_available_quantity_non_negative');
            DB::statement('ALTER TABLE resource_batches DROP CONSTRAINT IF EXISTS chk_batches_quantity_purchased_non_negative');
            DB::statement('ALTER TABLE resource_batches DROP CONSTRAINT IF EXISTS chk_batches_quantity_remaining_non_negative');
            DB::statement('ALTER TABLE resource_batches DROP CONSTRAINT IF EXISTS chk_batches_remaining_not_exceed_purchased');
        }

        Schema::table('resource_batches', function (Blueprint $table) {
            $table->dropColumn(['unit_type', 'conversion_factor']);
        });
    }
};
