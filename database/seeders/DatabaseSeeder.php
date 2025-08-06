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
            JabatanSeeder::class,
            MitraBayarSeeder::class,
            ProdukLendingSeeder::class,
            StatusDapemSeeder::class,
            StatusKerjaSeeder::class,
            SumberPembayaranLendingSeeder::class,
        ]);
    }
}
