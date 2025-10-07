<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert the two company records with specific IDs
        Company::create([
            'id' => 1,
            'name' => 'Mitra Karya Analitika',
            'logo' => 'https://via.placeholder.com/150x150/4F46E5/FFFFFF?text=MKA',
            'description' => 'Leading recruitment and human resources consulting company specializing in talent acquisition and workforce development.',
            'email' => 'info@mitrakarya.com',
            'phone' => '+62 21 1234 5678',
            'address' => 'Jl. Sudirman No. 123, Jakarta Selatan 12190, Indonesia',
            'website' => 'https://www.mitrakarya.com',
            'featured' => true,
            'display_order' => 1,
        ]);

        Company::create([
            'id' => 2,
            'name' => 'Autentik Karya Analitika',
            'logo' => 'https://via.placeholder.com/150x150/059669/FFFFFF?text=AKA',
            'description' => 'Innovative recruitment solutions provider focused on digital transformation and modern hiring practices.',
            'email' => 'info@autentikkarya.com',
            'phone' => '+62 21 8765 4321',
            'address' => 'Jl. Thamrin No. 456, Jakarta Pusat 10310, Indonesia',
            'website' => 'https://www.autentikkarya.com',
            'featured' => false,
            'display_order' => 2,
        ]);
    }
}
