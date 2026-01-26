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
        Schema::create('resource_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->foreignId('to_project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->enum('transfer_type', ['warehouse_to_project', 'project_to_project', 'project_to_warehouse']);
            $table->text('notes')->nullable();
            $table->foreignId('transferred_by')->nullable()->constrained('users');
            $table->timestamp('transferred_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_transfers');
    }
};
