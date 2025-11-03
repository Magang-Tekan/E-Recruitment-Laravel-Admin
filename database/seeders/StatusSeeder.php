<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if statuses already exist (they're seeded by migration)
        if (Status::count() > 0) {
            $this->command->info('Statuses already exist, skipping...');
            return;
        }

        // Fallback if migration didn't seed properly
        $statuses = [
            [
                'name' => 'Administrative Selection',
                'code' => 'admin_selection',
                'description' => 'Candidate is in administrative selection stage',
                'stage' => 'administrative_selection',
                'is_active' => true,
            ],
            [
                'name' => 'Psychological Test',
                'code' => 'psychotest',
                'description' => 'Candidate is taking psychological test',
                'stage' => 'psychological_test',
                'is_active' => true,
            ],
            [
                'name' => 'Interview',
                'code' => 'interview',
                'description' => 'Candidate is in interview stage',
                'stage' => 'interview',
                'is_active' => true,
            ],
            [
                'name' => 'Accepted',
                'code' => 'accepted',
                'description' => 'Candidate has been accepted',
                'stage' => 'accepted',
                'is_active' => true,
            ],
            [
                'name' => 'Rejected',
                'code' => 'rejected',
                'description' => 'Candidate has been rejected',
                'stage' => 'rejected',
                'is_active' => true,
            ],
        ];

        foreach ($statuses as $status) {
            Status::create($status);
        }

        $this->command->info('Status seeded successfully.');
    }
}
