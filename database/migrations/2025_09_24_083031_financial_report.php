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
        Schema::create('financial_report', function (Blueprint $table){
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('transaction_title');
            $table->enum('type', ['income', 'expense', 'asset', 'liability']);
            $table->decimal('amount', 15, 2);
            $table->dateTime('transaction_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_report');
    }
};


