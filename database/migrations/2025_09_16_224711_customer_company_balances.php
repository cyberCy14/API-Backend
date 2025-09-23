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
        Schema::create('customer_company_balances', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('customer_id');

                $table->foreignId('company_id')
                    ->nullable()
                    ->constrained('companies')
                    ->onDelete('cascade');

                $table->bigInteger('total_balance')->default(0);
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_company_balances');
    }
};
