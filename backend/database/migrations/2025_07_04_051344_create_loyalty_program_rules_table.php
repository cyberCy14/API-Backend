<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use phpDocumentor\Reflection\PseudoTypes\True_;

use function GuzzleHttp\default_ca_bundle;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loyaltyProgramRules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loyalty_program_id');
            $table->foreign('loyalty_program_id')->references('id')->on('loyaltyPrograms')->onDelete('cascade');


            $table->string('rule_name', 255);
            $table->enum('rule_type', ['purchase_based', 'referral', 'bonus'])->default('purchase_based');

            $table->unsignedBigInteger('points_earned')->default(0);
            $table->decimal('amount_per_point', 10 , 2)->nullable();
            $table->decimal('min_purchase_amount', 10, 2)->nullable();

            $table->unsignedBigInteger('product_category_id')->nullable();
            $table->unsignedBigInteger('product_item_id')->nullable();

            $table->boolean('is_active')->default(true);
            $table->date('active_from_date')->nullable();
            $table->date('active_to_date')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyaltyProgramRules');
    }
};
