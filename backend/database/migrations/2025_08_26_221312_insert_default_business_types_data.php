<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $businessTypes = [
            ['type' => 'Restaurant', 'description' => 'Food service and dining establishments', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Information Technology (IT)', 'description' => 'Software development, IT services, and technology solutions', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Retail Store', 'description' => 'Shops selling goods directly to consumers', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Construction', 'description' => 'Building, renovation, and construction services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Healthcare', 'description' => 'Medical services, clinics, and health facilities', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Automotive', 'description' => 'Car dealerships, repair shops, and automotive services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Beauty & Wellness', 'description' => 'Salons, spas, and wellness centers', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Education & Training', 'description' => 'Schools, training centers, and educational services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Real Estate', 'description' => 'Property sales, rentals, and real estate services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Manufacturing', 'description' => 'Production and manufacturing of goods', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Transportation & Logistics', 'description' => 'Shipping, delivery, and transportation services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Financial Services', 'description' => 'Banking, insurance, and financial consulting', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Marketing & Advertising', 'description' => 'Marketing agencies and advertising services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Legal Services', 'description' => 'Law firms and legal consulting', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Consulting', 'description' => 'Business and professional consulting services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'E-commerce', 'description' => 'Online retail and digital commerce', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Agriculture', 'description' => 'Farming, livestock, and agricultural services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Entertainment', 'description' => 'Event planning, entertainment venues, and media', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Hospitality', 'description' => 'Hotels, resorts, and accommodation services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Fitness & Sports', 'description' => 'Gyms, sports facilities, and fitness services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Photography', 'description' => 'Photography studios and professional photography services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Cleaning Services', 'description' => 'Residential and commercial cleaning services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Security Services', 'description' => 'Security agencies and protection services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Telecommunications', 'description' => 'Phone, internet, and communication services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Travel & Tourism', 'description' => 'Travel agencies and tourism services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Printing & Publishing', 'description' => 'Printing services and publishing companies', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Pet Services', 'description' => 'Veterinary clinics, pet grooming, and pet care', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Home Services', 'description' => 'Plumbing, electrical, and home maintenance services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Grocery & Supermarket', 'description' => 'Food retail and grocery stores', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Pharmacy', 'description' => 'Pharmaceutical retail and medical supplies', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('business_types')->insert($businessTypes);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $businessTypeNames = [
            'Restaurant',
            'Information Technology (IT)',
            'Retail Store',
            'Construction',
            'Healthcare',
            'Automotive',
            'Beauty & Wellness',
            'Education & Training',
            'Real Estate',
            'Manufacturing',
            'Transportation & Logistics',
            'Financial Services',
            'Marketing & Advertising',
            'Legal Services',
            'Consulting',
            'E-commerce',
            'Agriculture',
            'Entertainment',
            'Hospitality',
            'Fitness & Sports',
            'Photography',
            'Cleaning Services',
            'Security Services',
            'Telecommunications',
            'Travel & Tourism',
            'Printing & Publishing',
            'Pet Services',
            'Home Services',
            'Grocery & Supermarket',
            'Pharmacy',
        ];

        DB::table('business_types')->whereIn('type', $businessTypeNames)->delete();
    }
};