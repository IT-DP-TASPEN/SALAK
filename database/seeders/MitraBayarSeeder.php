<?php

namespace Database\Seeders;

use App\Models\MitraBayar;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MitraBayarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mitras = [
            'BANK BRI',
            'BANK MANTAP',
            'PT. POS (REKENING)',
            'BANK SMBC INDONESIA',
            'BANK JATENG',
            'BANK JABAR BANTEN',
            'BANK SYARIAH INDONESIA',
            'BANK JATIM',
            'BANK BNI',
            'BANK WOORI SAUDARA',
            'BANK BUKOPIN',
            'BANK SUMUT',
            'BANK BPD DIY',
            'BANK NAGARI',
            'BANK BPD BALI',
            'BANK ACEH',
            'BANK SUMSEL BABEL',
            'BANK DKI',
            'BANK SULSEL',
            'BANK KALBAR PONTIANAK',
            // 'BANK DP TASPEN',
            'BANK KALSEL',
            'BANK KALTIM',
            'BANK SULUT',
            'BANK RIAU',
            'BANK JAMBI',
            'BANK BTN',
            'BANK SULTRA',
            'BANK NTB',
            'BPR MODERN EXPRESS AMBON',
            'BANK NTT',
            'BANK LAMPUNG',
            'BANK BANTEN',
            'BANK KALTENG',
            'BANK BENGKULU',
            'BANK DANA RAYA',
            'BANK SULTENG',
            'BANK MALUKU',
            'BANK PAPUA',
            'BANK BUMI ARTHA',
            'BANK CAPITAL',
            'BANK MNC',
            'BANK ALADIN SYARIAH',
            'BANK MUAMALAT',
            'BANK MANDIRI',
            'KOPERASI',
        ];

        foreach ($mitras as $mitra) {
            MitraBayar::updateOrCreate(
                ['mitra_nama' => $mitra],
                ['mitra_nama' => $mitra]
            );
        }

        $this->command->info('Mitra Bayar seeded successfully!');
    }
}
