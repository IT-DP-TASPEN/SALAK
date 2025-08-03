<?php

namespace Database\Seeders;

use App\Models\SumberPembayaranLending;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SumberPembayaranLendingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources =  [
            'GAJI',
            'TUNJ CUTI',
            'JASPROD',
            'THT PENSIUN'
        ];

        foreach ($sources as $source) {
            SumberPembayaranLending::updateOrCreate(
                ['sumber_nama' => $source],
                ['sumber_nama' => $source]
            );
        }

        $this->command->info('Sumber Pembayaran Lending seeded successfully.');
    }
}
