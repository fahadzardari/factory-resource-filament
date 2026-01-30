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
        // Inventory Transactions - Most frequently queried table
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->index('resource_id'); // For filtering by resource
            $table->index('project_id'); // For filtering by project
            $table->index('transaction_date'); // For date-based queries
            $table->index('transaction_type'); // For filtering by type
            $table->index(['resource_id', 'transaction_date']); // Composite for resource timeline
            $table->index(['project_id', 'transaction_date']); // Composite for project timeline
            $table->index(['resource_id', 'project_id']); // For stock calculations
        });

        // Resources - Frequently accessed for dropdowns and lists
        Schema::table('resources', function (Blueprint $table) {
            $table->index('sku'); // For searching by SKU
            $table->index('category'); // For filtering by category
            $table->index('base_unit'); // For grouping by unit
        });

        // Projects - For status filtering and searches
        Schema::table('projects', function (Blueprint $table) {
            $table->index('status'); // For filtering active/pending/completed
            $table->index('code'); // For searching by project code
            $table->index(['status', 'start_date']); // For active projects report
        });

        // Users - For authentication and role-based access
        Schema::table('users', function (Blueprint $table) {
            $table->index('role'); // For filtering by role
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex(['resource_id']);
            $table->dropIndex(['project_id']);
            $table->dropIndex(['transaction_date']);
            $table->dropIndex(['transaction_type']);
            $table->dropIndex(['resource_id', 'transaction_date']);
            $table->dropIndex(['project_id', 'transaction_date']);
            $table->dropIndex(['resource_id', 'project_id']);
        });

        Schema::table('resources', function (Blueprint $table) {
            $table->dropIndex(['sku']);
            $table->dropIndex(['category']);
            $table->dropIndex(['base_unit']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['code']);
            $table->dropIndex(['status', 'start_date']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });
    }
};
