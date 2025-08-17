<?php

namespace Database\Seeders;

use App\Models\ProyeksiLendingProgressStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProyeksiLendingProgressStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            'PROSES BOSS',
            'BOOKING',
            'PENDING',
            'REJECT',
        ];

        foreach ($statuses as $status) {
            ProyeksiLendingProgressStatus::updateOrCreate(
                ['progress_status' => $status],
                ['progress_status' => $status]
            );
        }

        $this->command->info('Proyeksi Lending Progress Statuses seeded successfully.');
    }
}
