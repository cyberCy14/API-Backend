<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('loyalty_program_rules', function (Blueprint $table) {
        if (!Schema::hasColumn('loyalty_program_rules', 'loyalty_program_id')) {
            $table->foreignId('loyalty_program_id')
                  ->constrained('loyalty_programs')
                  ->onDelete('cascade');
        }
    });
}

public function down()
{
    Schema::table('loyalty_program_rules', function (Blueprint $table) {
        $table->dropForeign(['loyalty_program_id']);
        $table->dropColumn('loyalty_program_id');
    });
}

};
