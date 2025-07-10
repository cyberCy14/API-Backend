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
        Schema::create('productItems', function (Blueprint $table) {
            $table->id();
            
            // Basic product information
            $table->string('item_name', 255);
            $table->string('sku', 100)->unique(); // Stock Keeping Unit
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
    });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_items');
    }
};
