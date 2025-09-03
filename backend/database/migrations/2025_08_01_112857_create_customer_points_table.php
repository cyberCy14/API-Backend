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

        // ✅ Add constraint only for databases that support it
        if (DB::getDriverName() === 'sqlite') {
            // SQLite allows CHECK constraints if added when creating table,
            // so we simulate it with raw SQL
            DB::statement(
                "CREATE TEMP TRIGGER customer_points_customer_check
                 BEFORE INSERT ON customer_points
                 WHEN NEW.customer_id IS NULL AND NEW.customer_email IS NULL
                 BEGIN
                   SELECT RAISE(FAIL, 'customer_id or customer_email must be provided');
                 END;"
            );
        } else {
            DB::statement(
                'ALTER TABLE customer_points ADD CONSTRAINT customer_identifier_check CHECK ((customer_id IS NOT NULL) OR (customer_email IS NOT NULL))'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS customer_points_customer_check');
        }

        Schema::dropIfExists('customer_points');
    }
};
