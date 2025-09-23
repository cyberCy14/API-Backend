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
            $table->dropIndex(['customer_id', 'company_id']);
            $table->dropColumn('customer_id');
            $table->foreignId('customer_id')->nullable()->constrained('users')->after('id');
            $table->index(['customer_id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_points', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'company_id']);
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
            $table->string('customer_id')->nullable()->after('id');
            $table->index(['customer_id', 'company_id']);
        });
    }
};