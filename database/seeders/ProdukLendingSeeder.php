<?php

namespace Database\Seeders;

use App\Models\ProdukLending;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProdukLendingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            'REGULER PRA PENSIUN',
            'REGULER PENSIUN',
            'PLATINUM',
            'PLATINUM PLUS',
            'DISKONTO',
            'KREDIT PEGAWAI AKTIF',
        ];

        foreach ($products as $product) {
            ProdukLending::updateOrCreate(
                ['produk_nama' => $product],
                ['produk_nama' => $product]
            );
        }

        $this->command->info('Produk Lending seeded successfully.');
    }
}
