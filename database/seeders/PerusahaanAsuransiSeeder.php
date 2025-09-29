<?php

namespace Database\Seeders;

use App\Models\PerusahaanAsuransi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PerusahaanAsuransiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $asuransis = [
            [
                'asuransi_npwp' => '014552202093002',
                'asuransi_nama' => 'JASA RAHARJA PUTERA',
                'asuransi_alamat' => 'JL TB SIMATUPANG JAKARTA SELATAN',
                'asuransi_telepon' => '02178844444',
            ],
            [
                'asuransi_npwp' => '665064796093000',
                'asuransi_nama' => 'TASPEN LIFE',
                'asuransi_alamat' => 'GD ARTHALOKA JL JEND SUDIRMAN KAV2 LANTAI 11 JAKARTA',
                'asuransi_telepon' => '02157933306',
            ],
            [
                'asuransi_npwp' => '013076005022000',
                'asuransi_nama' => 'PT GLOBAL INSURANCE BROKER',
                'asuransi_alamat' => 'JL. SENOPATI NO. 21 KEBAYORAN BARU, JAKARTA',
                'asuransi_telepon' => '02127088138',
            ],
            [
                'asuransi_npwp' => '022451561003000',
                'asuransi_nama' => 'AA PIALANG',
                'asuransi_alamat' => 'Jl. TB Simatupang Pondok Pinang Kebayoran Lama Jaksel',
                'asuransi_telepon' => '0217653919',
            ],
            [
                'asuransi_npwp' => '468727214',
                'asuransi_nama' => 'PT ASURANSI NASIONAL LIFE',
                'asuransi_alamat' => 'Gedung Menara Jamsostek. Menara Utara Lt. 3A Jl. Jenderal Gatot Subroto. No. 38. Jakarta Selatan',
                'asuransi_telepon' => '(021) 29181999',
            ],
            [
                'asuransi_npwp' => '12334',
                'asuransi_nama' => 'PT ASURANSI BHAKTI BHAYANGKARA',
                'asuransi_alamat' => 'JL. PALATEHAN NO.5 KEBAYORAN BARU',
                'asuransi_telepon' => '134565',
            ],
            [
                'asuransi_npwp' => '000000000000000',
                'asuransi_nama' => 'ASURANSI BPJS',
                'asuransi_alamat' => 'JL.LETJEN SUPRAPTO NO.14 RT.010 RW.007',
                'asuransi_telepon' => '0817176737',
            ],
        ];

        foreach ($asuransis as $asuransi) {
            PerusahaanAsuransi::updateOrCreate(
                [
                    'asuransi_npwp' => $asuransi['asuransi_npwp'],
                    'asuransi_nama' => $asuransi['asuransi_nama'],
                ],
                $asuransi
            );
        }

        $this->command->info('Perusahaan Asuransi data seeded!');
    }
}
