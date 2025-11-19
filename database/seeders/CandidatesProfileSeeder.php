<?php

namespace Database\Seeders;

use App\Models\CandidatesProfile;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CandidatesProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus semua profile yang ada
        CandidatesProfile::query()->delete();

        // Get candidate users (hanya 3 candidate)
        $candidateUsers = User::where('role', UserRole::CANDIDATE)->orderBy('id')->get();

        // Profile untuk Candidate 1 - Ahmad Rizki Pratama
        if ($candidateUsers->count() > 0) {
            $user1 = $candidateUsers[0];
            CandidatesProfile::create([
                'user_id' => $user1->id,
                'no_ektp' => $user1->no_ektp,
                'gender' => 'Laki-laki',
                'phone_number' => '081234567890',
                'npwp' => '12.345.678.9-123.000',
                'about_me' => 'Saya adalah lulusan S1 Teknik Informatika dengan passion di bidang pengembangan software dan teknologi. Memiliki pengalaman dalam pengembangan aplikasi web menggunakan PHP Laravel, JavaScript, dan React. Saya senang belajar teknologi baru dan selalu berusaha memberikan solusi terbaik. Memiliki kemampuan problem solving yang baik dan dapat bekerja dalam tim maupun individu.',
                'place_of_birth' => 'Jakarta',
                'date_of_birth' => now()->subYears(25)->subMonths(3),
                'address' => 'Jl. Sudirman No. 123, RT 05 RW 02',
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Pusat',
                'district' => 'Menteng',
                'village' => 'Menteng Atas',
                'rt' => '05',
                'rw' => '02',
            ]);
        }

        // Profile untuk Candidate 2 - Siti Nurhaliza
        if ($candidateUsers->count() > 1) {
            $user2 = $candidateUsers[1];
            CandidatesProfile::create([
                'user_id' => $user2->id,
                'no_ektp' => $user2->no_ektp,
                'gender' => 'Perempuan',
                'phone_number' => '081234567891',
                'npwp' => '12.345.678.9-124.000',
                'about_me' => 'Saya adalah lulusan S1 Teknik Informatika yang memiliki minat besar dalam pengembangan aplikasi mobile dan web. Menguasai bahasa pemrograman Java, Python, dan JavaScript dengan framework React Native dan Node.js. Memiliki pengalaman dalam database management dan API development. Saya adalah pribadi yang detail-oriented, komunikatif, dan selalu ingin berkembang dalam karir teknologi.',
                'place_of_birth' => 'Bandung',
                'date_of_birth' => now()->subYears(24)->subMonths(6),
                'address' => 'Jl. Gatot Subroto No. 456, RT 03 RW 01',
                'province' => 'Jawa Barat',
                'city' => 'Bandung',
                'district' => 'Cimahi',
                'village' => 'Cimahi Utara',
                'rt' => '03',
                'rw' => '01',
            ]);
        }

        // Profile untuk Candidate 3 - Budi Santoso
        if ($candidateUsers->count() > 2) {
            $user3 = $candidateUsers[2];
            CandidatesProfile::create([
                'user_id' => $user3->id,
                'no_ektp' => $user3->no_ektp,
                'gender' => 'Laki-laki',
                'phone_number' => '081234567892',
                'npwp' => '12.345.678.9-125.000',
                'about_me' => 'Saya adalah fresh graduate S1 Teknik Informatika yang antusias dalam dunia software development. Memiliki dasar yang kuat dalam algoritma, struktur data, dan pemrograman berorientasi objek. Menguasai bahasa pemrograman Python, Java, dan JavaScript dengan framework Django dan Vue.js. Saya adalah quick learner, memiliki semangat tinggi untuk belajar, dan siap berkontribusi dalam tim development.',
                'place_of_birth' => 'Surabaya',
                'date_of_birth' => now()->subYears(23)->subMonths(9),
                'address' => 'Jl. Ahmad Yani No. 789, RT 07 RW 03',
                'province' => 'Jawa Timur',
                'city' => 'Surabaya',
                'district' => 'Sukolilo',
                'village' => 'Keputih',
                'rt' => '07',
                'rw' => '03',
            ]);
        }
    }

} 