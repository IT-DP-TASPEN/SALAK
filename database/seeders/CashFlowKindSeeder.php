<?php

namespace Database\Seeders;

use App\Models\CashFlowKind;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CashFlowKindSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kinds = [
            ['kind_name' => 'Tabungan', 'kind_type' => 'Cash In', 'kind_description' => 'Setoran tabungan dari nasabah', 'kind_pusat_only' => false],
            ['kind_name' => 'Deposito', 'kind_type' => 'Cash In', 'kind_description' => 'Penempatan Deposito', 'kind_pusat_only' => false],
            ['kind_name' => 'Pinjaman yang Diterima (ABP)', 'kind_type' => 'Cash In', 'kind_description' => 'Penerimaan pinjaman dari ABP', 'kind_pusat_only' => true],
            ['kind_name' => 'Dapem', 'kind_type' => 'Cash In', 'kind_description' => 'Dana Dapem', 'kind_pusat_only' => true],
            ['kind_name' => 'Non-Dapem', 'kind_type' => 'Cash In', 'kind_description' => 'Dana Non-Dapem', 'kind_pusat_only' => true],
            ['kind_name' => 'Klaim Asuransi', 'kind_type' => 'Cash In', 'kind_description' => 'Penerimaan klaim asuransi', 'kind_pusat_only' => true],
            ['kind_name' => 'Pelunasan Kredit', 'kind_type' => 'Cash In', 'kind_description' => 'Pelunasan kredit', 'kind_pusat_only' => false],
            ['kind_name' => 'Fee Based', 'kind_type' => 'Cash In', 'kind_description' => 'Penerimaan fee based', 'kind_pusat_only' => true],
            ['kind_name' => 'Lain-lain', 'kind_type' => 'Cash In', 'kind_description' => 'Penerimaan lain-lain', 'kind_pusat_only' => false],

            ['kind_name' => 'Penarikan Tabungan', 'kind_type' => 'Cash Out', 'kind_description' => 'Penarikan tabungan oleh nasabah', 'kind_pusat_only' => false],
            ['kind_name' => 'Pencairan Deposito', 'kind_type' => 'Cash Out', 'kind_description' => 'Pencairan deposito oleh nasabah', 'kind_pusat_only' => false],
            ['kind_name' => 'Pencairan Kredit', 'kind_type' => 'Cash Out', 'kind_description' => 'Pencairan kredit kepada nasabah', 'kind_pusat_only' => false],
            ['kind_name' => 'Pengeluaran Biaya Operasional', 'kind_type' => 'Cash Out', 'kind_description' => 'Pengeluaran biaya operasional kantor cabang', 'kind_pusat_only' => false],
            ['kind_name' => 'Pengeluaran Biaya Non-Operasional', 'kind_type' => 'Cash Out', 'kind_description' => 'Pengeluaran biaya non-operasional kantor cabang', 'kind_pusat_only' => false],
            ['kind_name' => 'Lain-lain', 'kind_type' => 'Cash Out', 'kind_description' => 'Pengeluaran lain-lain', 'kind_pusat_only' => false],
        ];

        foreach ($kinds as $kind) {
            CashFlowKind::updateOrCreate(
                ['kind_name' => $kind['kind_name'], 'kind_type' => $kind['kind_type']],
                $kind
            );
        }

        $this->command->info('Cash flow kinds seeded successfully.');
    }
}
