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
        Schema::create('project_resource_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->date('consumption_date');
            $table->decimal('opening_balance', 10, 2)->comment('Available quantity at start of day');
            $table->decimal('quantity_consumed', 10, 2);
            $table->decimal('closing_balance', 10, 2)->comment('Available quantity at end of day');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Removed unique constraint - allow multiple consumption records per day
            // $table->unique(['project_id', 'resource_id', 'consumption_date'], 'proj_res_cons_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_resource_consumptions');
    }
};
