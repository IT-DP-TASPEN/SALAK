<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BranchOfficeSeeder::class,
            CashFlowKindSeeder::class,
            JabatanSeeder::class,
            MitraBayarSeeder::class,
            PerusahaanAsuransiSeeder::class,
            ProdukFundingSeeder::class,
            ProdukLendingSeeder::class,
            ProyeksiLendingProgressStatusSeeder::class,
            StatusDapemSeeder::class,
            StatusKerjaSeeder::class,
            SumberPembayaranLendingSeeder::class,

            AdminSeeder::class,
            ShieldSeeder::class,
        ]);
    }
}
