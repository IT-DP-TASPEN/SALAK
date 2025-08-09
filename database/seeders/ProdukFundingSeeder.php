<?php

namespace Database\Seeders;

use App\Models\ProdukFunding;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProdukFundingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            'Deposito' => [
                ['produk_nama' => 'Deposito'],
            ],
            'Tabungan' => [
                ['produk_nama' => 'Bujang Umroh'],
                ['produk_nama' => 'Bujang Qurban'],
                ['produk_nama' => 'Bujang Tour'],
                ['produk_nama' => 'Bujang Emas'],
                ['produk_nama' => 'Siseto'],
                ['produk_nama' => 'Friend'],
                ['produk_nama' => 'Tabur Reward Emas'],
                ['produk_nama' => 'Target'],
                ['produk_nama' => 'Tabitri'],
                ['produk_nama' => 'Tabungan Umum'],
                ['produk_nama' => 'Tabungan Pensiun'],
                ['produk_nama' => 'Tabungan Simpel'],
                ['produk_nama' => 'Dpentas Vaganza'],
            ],
        ];

        foreach ($products as $type => $items) {
            foreach ($items as $item) {
                ProdukFunding::updateOrCreate(
                    [
                        'produk_jenis' => $type,
                        'produk_nama' => $item['produk_nama'],
                    ],
                    [
                        'produk_jenis' => $type,
                        'produk_nama' => $item['produk_nama'],
                    ]
                );
            }
        }

        $this->command->info('Produk Funding seeded successfully.');
    }
}
