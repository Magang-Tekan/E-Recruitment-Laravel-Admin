<?php

namespace Database\Seeders;

use App\Models\CandidatesOrganization;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CandidatesOrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan semua organizations
        CandidatesOrganization::query()->delete();
        
        // Get candidate users
        $candidateUsers = User::where('role', UserRole::CANDIDATE)->get();

        $organizations = [
            // Student Organizations
            'Badan Eksekutif Mahasiswa (BEM)',
            'Dewan Perwakilan Mahasiswa (DPM)',
            'Himpunan Mahasiswa Jurusan',
            'Unit Kegiatan Mahasiswa (UKM)',
            'Organisasi Kemahasiswaan Daerah',
            'Senat Mahasiswa',
            'Lembaga Pers Mahasiswa',
            'Organisasi Rohani',
            'Koperasi Mahasiswa',
            'Paduan Suara Mahasiswa',
            
            // Professional Organizations
            'Ikatan Akuntan Indonesia (IAI)',
            'Ikatan Dokter Indonesia (IDI)',
            'Persatuan Insinyur Indonesia (PII)',
            'Ikatan Arsitek Indonesia (IAI)',
            'Himpunan Keahlian Teknik Indonesia',
            'Asosiasi Profesi Teknologi Informasi Indonesia',
            'Ikatan Sarjana Ekonomi Indonesia (ISEI)',
            'Persatuan Wartawan Indonesia (PWI)',
            'Ikatan Psikologi Indonesia (IPsI)',
            'Asosiasi Dosen Indonesia',
            
            // Social Organizations
            'Palang Merah Indonesia (PMI)',
            'Karang Taruna',
            'Gerakan Pramuka Indonesia',
            'Organisasi Kepemudaan Daerah',
            'Lembaga Swadaya Masyarakat',
            'Yayasan Sosial',
            'Komunitas Peduli Lingkungan',
            'Relawan Bencana',
            'Komunitas Literasi',
            'Organisasi Keagamaan',
            
            // Sports & Arts Organizations
            'Persatuan Sepak Bola Indonesia',
            'Persatuan Bulu Tangkis Indonesia',
            'Organisasi Seni Budaya',
            'Komunitas Fotografi',
            'Komunitas Musik',
            'Sanggar Tari',
            'Teater Kampus',
            'Komunitas Film',
            'Klub Olahraga',
            'Komunitas Running',
            
            // Technology & Innovation
            'Komunitas Developer Indonesia',
            'Google Developer Group',
            'Microsoft Student Partner',
            'Linux User Group',
            'Komunitas Open Source',
            'Startup Community',
            'Digital Innovation Hub',
            'Tech Meetup Jakarta',
            'Python Indonesia',
            'JavaScript Indonesia',
            
            // Business & Entrepreneurship
            'Kamar Dagang dan Industri Indonesia',
            'Asosiasi Pengusaha Indonesia',
            'Young Entrepreneurs Association',
            'Komunitas Bisnis Online',
            'Inkubator Bisnis',
            'Angel Investor Network',
            'Business Networking Group',
            'Entrepreneur Club',
            'Startup Accelerator',
            'E-commerce Association',
        ];

        $positions = [
            'Ketua', 'Wakil Ketua', 'Sekretaris', 'Bendahara', 'Koordinator',
            'Ketua Divisi', 'Wakil Koordinator', 'Staff Ahli', 'Anggota Aktif',
            'Kepala Departemen', 'Manager', 'Supervisor', 'Team Leader',
            'Project Manager', 'Event Coordinator', 'Public Relations',
            'Treasurer', 'Secretary General', 'Vice President', 'Board Member',
            'Committee Member', 'Volunteer', 'Ambassador', 'Mentor',
            'Advisor', 'Consultant', 'Representative', 'Delegate'
        ];

        $months = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        // Tidak membuat organizations untuk candidates
    }

    private function generateOrganizationDescription($organization, $position)
    {
        $descriptions = [
            'Bertanggung jawab dalam mengkoordinasi kegiatan organisasi dan memimpin tim untuk mencapai tujuan bersama.',
            'Mengelola program kerja organisasi dan memastikan pelaksanaan kegiatan berjalan sesuai rencana.',
            'Mengembangkan strategi organisasi untuk meningkatkan partisipasi anggota dan dampak positif di masyarakat.',
            'Memimpin berbagai project dan initiative untuk kemajuan organisasi dan pengembangan anggota.',
            'Berperan aktif dalam planning, organizing, dan executing berbagai event dan program organisasi.',
            'Menjalin kerjasama dengan berbagai stakeholder untuk mendukung visi misi organisasi.',
            'Mengkoordinasi tim dan memastikan target organisasi tercapai dengan efektif dan efisien.',
            'Bertanggung jawab dalam pengembangan SDM dan capacity building anggota organisasi.',
        ];
        
        return $descriptions[array_rand($descriptions)] . ' Berposisi sebagai ' . $position . ' di ' . $organization . '.';
    }
} 