<?php

namespace Database\Seeders;

use App\Models\CandidatesEducation;
use App\Models\EducationLevel;
use App\Models\MasterMajor;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CandidatesEducationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus semua education yang ada
        CandidatesEducation::query()->delete();

        // Get candidate users (hanya 3 candidate)
        $candidateUsers = User::where('role', UserRole::CANDIDATE)->orderBy('id')->get();
        $majors = MasterMajor::all();
        $educationLevels = EducationLevel::all();
        
        // Ensure required data exists
        if ($majors->isEmpty()) {
            $this->command->info('No majors found. Running MasterMajorSeeder first.');
            $this->call(MasterMajorSeeder::class);
            $majors = MasterMajor::all();
        }
        
        if ($educationLevels->isEmpty()) {
            $this->command->info('No education levels found. Running EducationLevelSeeder first.');
            $this->call(EducationLevelSeeder::class);
            $educationLevels = EducationLevel::all();
        }

        // Get S1 level dan Teknik Informatika major
        $s1Level = $educationLevels->where('name', 'D4/S1')->first();
        $smaLevel = $educationLevels->where('name', 'SMA/SMK')->first();
        $informatikaMajor = $majors->where('name', 'Teknik Informatika')->first();
        $smaMajor = $majors->where('name', 'Matematika')->first() ?? $majors->first();

        // Get Akuntansi major
        $akuntansiMajor = $majors->where('name', 'Akuntansi')->first();
        
        foreach ($candidateUsers as $index => $user) {
            // Budi Santoso gets D4/S1 Akuntansi
            if ($user->email === 'budi.santoso@gmail.com' || $user->name === 'Budi Santoso') {
                // Budi Santoso - D4/S1 Akuntansi
                if ($s1Level && $akuntansiMajor) {
                    CandidatesEducation::create([
                        'user_id' => $user->id,
                        'education_level_id' => $s1Level->id,
                        'faculty' => 'Fakultas Ekonomi dan Bisnis',
                        'major_id' => $akuntansiMajor->id,
                        'institution_name' => 'Universitas Indonesia',
                        'gpa' => 3.65,
                        'year_in' => 2019,
                        'year_out' => 2023,
                    ]);
                }
            } else {
                // Other candidates - S1 Teknik Informatika
                if ($s1Level && $informatikaMajor) {
                    $institutions = [
                        'Universitas Indonesia',
                        'Institut Teknologi Bandung',
                        'Universitas Gadjah Mada',
                        'Institut Teknologi Sepuluh Nopember',
                        'Universitas Bina Nusantara',
                        'Universitas Gunadarma',
                    ];
                    
                    $gpas = [3.45, 3.60, 3.75, 3.80];
                    $startYears = [2017, 2018, 2019, 2020];
                    $endYears = [2021, 2022, 2023, 2024];
                    
                    CandidatesEducation::create([
                        'user_id' => $user->id,
                        'education_level_id' => $s1Level->id,
                        'faculty' => 'Fakultas Ilmu Komputer',
                        'major_id' => $informatikaMajor->id,
                        'institution_name' => $institutions[$index % count($institutions)],
                        'gpa' => $gpas[$index % count($gpas)],
                        'year_in' => $startYears[$index % count($startYears)],
                        'year_out' => $endYears[$index % count($endYears)],
                    ]);
                }
            }
            
            // SMA untuk semua candidate
            if ($smaLevel && $smaMajor) {
                $smaInstitutions = [
                    'SMA Negeri 8 Jakarta',
                    'SMA Negeri 3 Bandung',
                    'SMA Negeri 5 Surabaya',
                ];
                
                $smaGpas = [3.70, 3.80, 3.85];
                $smaStartYears = [2014, 2015, 2016];
                $smaEndYears = [2017, 2018, 2019];
                
                CandidatesEducation::create([
                    'user_id' => $user->id,
                    'education_level_id' => $smaLevel->id,
                    'faculty' => 'SMA',
                    'major_id' => $smaMajor->id,
                    'institution_name' => $smaInstitutions[$index % count($smaInstitutions)],
                    'gpa' => $smaGpas[$index % count($smaGpas)],
                    'year_in' => $smaStartYears[$index % count($smaStartYears)],
                    'year_out' => $smaEndYears[$index % count($smaEndYears)],
                ]);
            }
        }
    }

} 