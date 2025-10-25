<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE customer_points DROP CONSTRAINT IF EXISTS customer_points_status_check");

        DB::statement("
            ALTER TABLE customer_points 
            ADD CONSTRAINT customer_points_status_check 
            CHECK (status IN ('pending','completed','cancelled'))
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE customer_points DROP CONSTRAINT IF EXISTS customer_points_status_check");

        DB::statement("
            ALTER TABLE customer_points 
            ADD CONSTRAINT customer_points_status_check 
            CHECK (status IN ('pending','completed'))
        ");
    }
};
