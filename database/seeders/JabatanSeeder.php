<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JabatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jabatans = [
            ['jabatan_nama' => 'Account Officer', 'jabatan_target' => 1_250_000_000],
            ['jabatan_nama' => 'Marketing Kontrak Khusus (MKK)', 'jabatan_target' => 600_000_000],
            ['jabatan_nama' => 'Staff Marketing', 'jabatan_target' => 1_250_000_000],
            ['jabatan_nama' => 'Partner Bisnis Bank (PBB)', 'jabatan_target' => 600_000_000],
            ['jabatan_nama' => 'Agent Marketing (Freelance)', 'jabatan_target' => 0],
            ['jabatan_nama' => 'x-Account Officer', 'jabatan_target' => 0],
            ['jabatan_nama' => 'x-Marketing Kontrak Khusus (MKK)', 'jabatan_target' => 0],
            ['jabatan_nama' => 'x-Staff Marketing', 'jabatan_target' => 0],
        ];

        foreach ($jabatans as $jabatan) {
            Jabatan::updateOrCreate(
                ['jabatan_nama' => $jabatan['jabatan_nama']],
                $jabatan
            );
        }

        $this->command->info('Jabatan seeded successfully.');
    }
}
