<?php

namespace Database\Seeders;

use App\Models\StatusDapem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusDapemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            'MUTASI DARI BANK LAIN',
            'SUDAH DI BANK DP TASPEN',
            'BARU PENSIUN KBY BANK DP TASPEN',
            'DAPEM BANPOT BANK MANTAP',
            'LAINNYA (BUKAN PENSIUNAN)'
        ];

        foreach ($statuses as $status) {
            StatusDapem::updateOrCreate(
                ['dapem_nama' => $status],
                ['dapem_nama' => $status]
            );
        }

        $this->command->info('Status Dapem seeded successfully.');
    }
}
