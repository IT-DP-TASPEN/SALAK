<?php

namespace Database\Seeders;

use App\Models\BranchOffice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchOfficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branchOffices = [
            ['branch_code' => '00', 'branch_code_fincloud' => '000', 'branch_name' => 'Kantor Pusat Manajemen'],
            ['branch_code' => '01', 'branch_code_fincloud' => '001', 'branch_name' => 'Kantor Pusat Operasional'],
            ['branch_code' => '02', 'branch_code_fincloud' => '002', 'branch_name' => 'Kantor Cabang Bogor'],
            ['branch_code' => '03', 'branch_code_fincloud' => '003', 'branch_name' => 'Kantor Cabang Depok'],
            ['branch_code' => '04', 'branch_code_fincloud' => '004', 'branch_name' => 'Kantor Cabang Tangerang'],
            ['branch_code' => '05', 'branch_code_fincloud' => '005', 'branch_name' => 'Kantor Cabang Jakarta Timur'],
            ['branch_code' => '06', 'branch_code_fincloud' => '006', 'branch_name' => 'Kantor Cabang Karawang'],
            ['branch_code' => '07', 'branch_code_fincloud' => '007', 'branch_name' => 'Kantor Cabang Cikarang'],
            ['branch_code' => '08', 'branch_code_fincloud' => '008', 'branch_name' => 'Kantor Cabang Purwokerto'],
        ];

        foreach ($branchOffices as $branchOffice) {
            BranchOffice::updateOrCreate(
                ['branch_code' => $branchOffice['branch_code']],
                $branchOffice
            );
        }

        $this->command->info('Branch offices seeded successfully.');
    }
}
