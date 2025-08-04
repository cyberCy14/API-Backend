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
        Schema::create('company_transactions', function (Blueprint $table){
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            //link to rewards if redeeming
            $table->foreignId('reward_id')->nullable()->constrained('loyalty_rewards')->nullOnDelete();

            $table->enum('transaction_type', ['earn', 'redeem'])->default('earn');
            $table->string('description');
            $table->integer('poi    nts');
            $table->date('transaction_date')->index();

            //this will determine where or how the points/rewards/transactions came from
            //such as rules, programs, etc
            $table->string('source')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_transactions');
    }
};
