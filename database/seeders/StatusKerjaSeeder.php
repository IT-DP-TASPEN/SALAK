<?php

namespace Database\Seeders;

use App\Models\StatusKerja;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusKerjaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            'PENSIUN ASN',
            'PENSIUN DP TASPEN',
            'PEG TASPEN GROUP',
            'PEG BANK DP TASPEN',
            'PEG OJK',
            'PEG SWASTA LAINNYA',
            'PENSIUN ASABRI',
            'PRA PENSIUN ASN',
            'PRA PENSIUN DP TASPEN',
        ];

        foreach ($statuses as $status) {
            StatusKerja::updateOrCreate(
                ['kerja_nama' => $status],
                ['kerja_nama' => $status]
            );
        }

        $this->command->info('Status Kerja seeded successfully.');
    }
}
