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
        Schema::table('customer_points', function (Blueprint $table) {
            // Add reward-related columns
            $table->foreignId('reward_id')
                ->nullable()
                ->constrained('loyalty_rewards')
                ->onDelete('set null')
                ->after('loyalty_program_id');

            $table->string('reward_name')->nullable()->after('reward_id');
            $table->string('reward_type', 50)->nullable()->after('reward_name');

            // Add created_by column to track which handler created the transaction
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->after('reward_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_points', function (Blueprint $table) {
            // Drop reward-related columns
            $table->dropForeign(['reward_id']);
            $table->dropColumn(['reward_id', 'reward_name', 'reward_type']);

            // Drop created_by column
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};