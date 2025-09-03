<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_points', function (Blueprint $table) {
            $table->id();

            $table->string('customer_id')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('transaction_id')->nullable()->unique();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->onDelete('cascade');

            $table->foreignId('loyalty_program_id')
                ->nullable()
                ->constrained('loyalty_programs')
                ->onDelete('cascade');

            $table->integer('points_earned')->default(0);
            $table->decimal('purchase_amount', 10, 2)->nullable()->default(0.00);

            $table->timestamp('credited_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->text('redemption_description')->nullable();
            $table->dateTime('transaction_date')->nullable();
            $table->json('rule_breakdown')->nullable();

            $table->string('qr_code_path')->nullable();
            $table->enum('transaction_type', ['earning', 'redemption'])->default('earning');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamps();

            // Indexes
            $table->index(['customer_email', 'company_id']);
            $table->index(['customer_id', 'company_id']);
            $table->index(['transaction_id']);
            $table->index('status');
        });

        // Add a check constraint (works in PostgreSQL, MySQL 8+, MariaDB 10.2+)
        DB::statement(
            'ALTER TABLE customer_points ADD CONSTRAINT customer_identifier_check CHECK ((customer_id IS NOT NULL) OR (customer_email IS NOT NULL))'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the table completely (constraints and indexes will be dropped automatically)
        Schema::dropIfExists('customer_points');
    }
};
