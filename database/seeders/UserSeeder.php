<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    protected $faker;

    public function __construct()
    {
        $this->faker = Faker::create('id_ID');
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus semua user candidate yang ada
        DB::table('candidates_profiles')->whereIn('user_id', function($query) {
            $query->select('id')->from('users')->where('role', UserRole::CANDIDATE);
        })->delete();
        
        User::where('role', UserRole::CANDIDATE)->delete();
        User::where('role', UserRole::HR)->delete();

        // User HR
        User::create([
            'id' => 1,
            'name' => 'HR User',
            'email' => 'hr@gmail.com',
            'password' => Hash::make('password'),
            'role' => UserRole::HR,
            'no_ektp' => $this->faker->unique()->numerify('################'),
        ]);

        // Candidate 1 - Teknik Informatika
        User::create([
            'id' => 2,
            'name' => 'Ahmad Rizki Pratama',
            'email' => 'ahmad.rizki@gmail.com',
            'password' => Hash::make('password'),
            'role' => UserRole::CANDIDATE,
            'no_ektp' => $this->faker->unique()->numerify('3171##########'),
        ]);

        // Candidate 2 - Teknik Informatika
        User::create([
            'id' => 3,
            'name' => 'Siti Nurhaliza',
            'email' => 'siti.nurhaliza@gmail.com',
            'password' => Hash::make('password'),
            'role' => UserRole::CANDIDATE,
            'no_ektp' => $this->faker->unique()->numerify('3171##########'),
        ]);

        // Candidate 3 - Teknik Informatika
        User::create([
            'id' => 4,
            'name' => 'Budi Santoso',
            'email' => 'budi.santoso@gmail.com',
            'password' => Hash::make('password'),
            'role' => UserRole::CANDIDATE,
            'no_ektp' => $this->faker->unique()->numerify('3171##########'),
        ]);
    }
}
