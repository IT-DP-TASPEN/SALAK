<?php

namespace Database\Seeders;

use App\Models\JenisFunding;
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
            'ABP' => [
                ['produk_nama' => 'ABP'],
            ],
            'Deposito' => [
                ['produk_nama' => 'Deposito'],
                ['produk_nama' => 'Deposito Berjangka Perorangan'],
                ['produk_nama' => 'Deposito Berjangka Antar Bank'],
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
                $id = JenisFunding::firstOrCreate(
                    ['jenis_funding_nama' => $type],
                    ['jenis_funding_nama' => $type]
                )->id;
                ProdukFunding::updateOrCreate(
                    ['produk_nama' => $item['produk_nama']],
                    array_merge($item, ['produk_jenis' => $id])
                );
            }
        }

        $this->command->info('Produk Funding seeded successfully.');
    }
}
