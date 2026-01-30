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
        Schema::table('resource_batches', function (Blueprint $table) {
            // Add project_id to track batch location
            // NULL = Central Hub (warehouse)
            // NOT NULL = Belongs to specific project
            $table->foreignId('project_id')
                ->nullable()
                ->after('resource_id')
                ->constrained()
                ->onDelete('restrict'); // Prevent deleting projects with inventory
            
            // Add index for efficient queries
            $table->index(['resource_id', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resource_batches', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['resource_id', 'project_id']);
            $table->dropColumn('project_id');
        });
    }
};
