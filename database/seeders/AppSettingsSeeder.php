<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AppSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if app_settings table has proper columns
        if (!Schema::hasColumn('app_settings', 'key')) {
            $this->command->info('App settings table structure incomplete. Skipping seeding.');
            $this->command->info('Note: Add columns like key, value, description, type to app_settings table if needed.');
            return;
        }

        $settings = [
            [
                'key' => 'app_name',
                'value' => 'E-Recruitment System',
                'description' => 'Application name',
                'type' => 'text',
            ],
            [
                'key' => 'app_logo',
                'value' => '/images/logo.png',
                'description' => 'Application logo path',
                'type' => 'file',
            ],
            [
                'key' => 'recruitment_active',
                'value' => '1',
                'description' => 'Enable/disable recruitment system',
                'type' => 'boolean',
            ],
            [
                'key' => 'max_applications_per_candidate',
                'value' => '5',
                'description' => 'Maximum applications per candidate',
                'type' => 'number',
            ],
            [
                'key' => 'psychotest_duration',
                'value' => '60',
                'description' => 'Default psychological test duration in minutes',
                'type' => 'number',
            ],
            [
                'key' => 'interview_duration',
                'value' => '90',
                'description' => 'Default interview duration in minutes',
                'type' => 'number',
            ],
            [
                'key' => 'admin_email',
                'value' => 'admin@recruitment.com',
                'description' => 'Administrator email for notifications',
                'type' => 'email',
            ],
            [
                'key' => 'smtp_enabled',
                'value' => '0',
                'description' => 'Enable email notifications',
                'type' => 'boolean',
            ],
        ];

        foreach ($settings as $setting) {
            AppSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('App settings seeded successfully.');
    }
}
