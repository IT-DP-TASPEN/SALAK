<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportRatioCoaRecap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:export-ratio-coa {--path=rekap_kode_coa_rasio_keuangan.xlsx}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export rekap kode COA untuk rasio keuangan ke file Excel';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $relativePath = (string) $this->option('path');
        $absolutePath = base_path($relativePath);

        $directory = dirname($absolutePath);
        if (!is_dir($directory)) {
            File::ensureDirectoryExists($directory);
        }

        $rows = $this->rows();
        $headers = [
            'Rasio',
            'Komponen',
            'Peran',
            'Periode Berlaku',
            'Kode COA',
            'Sumber Data',
            'Sumber Kode',
            'Catatan',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap COA');

        foreach ($headers as $columnIndex => $header) {
            $sheet->setCellValueExplicitByColumnAndRow(
                $columnIndex + 1,
                1,
                $header,
                DataType::TYPE_STRING
            );
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValueExplicitByColumnAndRow(
                    $columnIndex + 1,
                    $rowIndex + 2,
                    $value,
                    DataType::TYPE_STRING
                );
            }
        }

        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($absolutePath);

        $this->info('Rekap COA rasio keuangan berhasil diexport.');
        $this->line('Lokasi file: ' . $absolutePath);

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function rows(): array
    {
        return [
            // PAR
            $this->row('PAR', 'Outstanding DPD >1 s.d <=90', 'Pembilang', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiPAR', 'loan_days_past_due > 1 dan <= 90'),
            $this->row('PAR', 'Total Outstanding Loan', 'Penyebut', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiPAR', ''),

            // NPL Gross
            $this->row('NPL Gross', 'Bad Loans (kolek 3,4,5)', 'Pembilang', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiNPL', ''),
            $this->row('NPL Gross', 'Total Outstanding Loan', 'Penyebut', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiNPL', ''),

            // NPL Nett
            $this->row('NPL Nett', 'Bad Loans (kolek 3,4,5)', 'Pembilang awal', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiNPLNett', ''),
            $this->row('NPL Nett', 'PPKA Khusus - Kolek 3', 'Pengurang pembilang', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiPPKAKhusus', 'Bobot 10%'),
            $this->row('NPL Nett', 'PPKA Khusus - Kolek 4', 'Pengurang pembilang', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiPPKAKhusus', 'Bobot 50%'),
            $this->row('NPL Nett', 'PPKA Khusus - Kolek 5', 'Pengurang pembilang', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiPPKAKhusus', 'Bobot 100%'),
            $this->row('NPL Nett', 'Total Outstanding Loan', 'Penyebut', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiNPLNett', ''),

            // CKPN per PPKA
            $this->row('CKPN per PPKA', 'CKPN', 'Pembilang', 'Semua tanggal', '1272005', 'SaldoNeraca', 'BranchOffice::konsolidasiCKPNPerPPKA', ''),
            $this->row('CKPN per PPKA', 'PPKA - Kolek 1', 'Penyebut', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiPPKA', 'Bobot 0.5%'),
            $this->row('CKPN per PPKA', 'PPKA - Kolek 2', 'Penyebut', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiPPKA', 'Bobot 3%'),
            $this->row('CKPN per PPKA', 'PPKA - Kolek 3', 'Penyebut', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiPPKA', 'Bobot 10%'),
            $this->row('CKPN per PPKA', 'PPKA - Kolek 4', 'Penyebut', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiPPKA', 'Bobot 50%'),
            $this->row('CKPN per PPKA', 'PPKA - Kolek 5', 'Penyebut', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiPPKA', 'Bobot 100%'),

            // BOPO
            $this->row('BOPO', 'Beban Operasional', 'Pembilang', 'Semua tanggal', '5', 'SaldoNeraca', 'BranchOffice::konsolidasiBebanOperasional', ''),
            $this->row('BOPO', 'Pendapatan Operasional', 'Penyebut', 'Semua tanggal', '4', 'SaldoNeraca', 'BranchOffice::konsolidasiPendapatanOperasional', ''),

            // NIM
            $this->row('NIM', 'Pendapatan Bunga', 'Pembilang', 'Sebelum 12 Oktober 2025', '2.112, 2.113, 2.115, 2.120.1', 'MSO', 'BranchOffice::konsolidasiNim', ''),
            $this->row('NIM', 'Beban Bunga', 'Pengurang pembilang', 'Sebelum 12 Oktober 2025', '2.171, 2.172, 2.166, 2.167, 2.168', 'MSO', 'BranchOffice::konsolidasiNim', ''),
            $this->row('NIM', 'Pendapatan Bunga', 'Pembilang', 'Mulai 12 Oktober 2025', '401, 402, 403, 410', 'SaldoNeraca', 'BranchOffice::konsolidasiNim', ''),
            $this->row('NIM', 'Beban Bunga', 'Pengurang pembilang', 'Mulai 12 Oktober 2025', '501, 502, 511', 'SaldoNeraca', 'BranchOffice::konsolidasiNim', ''),
            $this->row('NIM', 'Rata-rata Aset Produktif', 'Penyebut', 'Sebelum 12 Oktober 2025', '1.120, 1.130.1', 'MSO', 'BranchOffice::konsolidasiNim', ''),
            $this->row('NIM', 'Rata-rata Aset Produktif', 'Penyebut', 'Mulai 12 Oktober 2025', '110, 121', 'SaldoNeraca', 'BranchOffice::konsolidasiNim', ''),

            // ROA
            $this->row('ROA', 'Laba Bersih Sebelum Pajak', 'Pembilang', 'Sebelum 12 Oktober 2025', '2.330', 'MSO', 'BranchOffice::konsolidasiROA', ''),
            $this->row('ROA', 'Laba Bersih Sebelum Pajak', 'Pembilang', 'Mulai 12 Oktober 2025', '323, 558', 'SaldoNeraca', 'BranchOffice::konsolidasiROA', ''),
            $this->row('ROA', 'Total Asset', 'Penyebut', 'Sebelum 12 Oktober 2025', '1.190, 1.170', 'MSO', 'BranchOffice::konsolidasiROA', 'Rumus: 1.190 - 1.170'),
            $this->row('ROA', 'Total Asset', 'Penyebut', 'Mulai 12 Oktober 2025', '1', 'SaldoNeraca', 'BranchOffice::konsolidasiROA', ''),

            // LDR
            $this->row('LDR', 'Total Loans', 'Pembilang', 'Semua tanggal', '121', 'SaldoNeraca', 'BranchOffice::konsolidasiLDR', ''),
            $this->row('LDR', 'Total Deposits', 'Penyebut', 'Semua tanggal', '221, 2312200, 2312201', 'SaldoNeraca', 'BranchOffice::konsolidasiLDR', ''),

            // Cash Ratio
            $this->row('Cash Ratio', 'Aset Likuid', 'Pembilang', 'Semua tanggal', '100, 111, 112', 'SaldoNeraca', 'BranchOffice::konsolidasiAssetLiquid', 'Ada pengurang non-COA: branch_saldo_aba_blokir'),
            $this->row('Cash Ratio', 'Kewajiban Lancar', 'Penyebut', 'Semua tanggal', '211, 212, 213, 219, 2011008, 2011001, 2011004, 2011005, 2011006, 2011007, 208, 221, 2312200, 2312201', 'SaldoNeraca', 'BranchOffice::konsolidasiKewajibanLancar', ''),

            // KPMM
            $this->row('KPMM', 'Modal Inti - Penambah', 'Komponen modal', 'Semua tanggal', '300, 3111000, 3111001, 322, 323', 'SaldoNeraca', 'BranchOffice::konsolidasiModalInti', ''),
            $this->row('KPMM', 'Modal Inti - Pengurang', 'Komponen modal', 'Semua tanggal', '302', 'SaldoNeraca', 'BranchOffice::konsolidasiModalInti', ''),
            $this->row('KPMM', 'CKPN pembanding PPKA', 'Komponen modal', 'Semua tanggal', '1272005', 'SaldoNeraca', 'BranchOffice::konsolidasiModalInti', ''),
            $this->row('KPMM', 'PPKA (kolek 1..5)', 'Komponen modal', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiPPKA', 'Jika CKPN < PPKA maka selisih jadi pengurang modal inti'),
            $this->row('KPMM', 'Modal Pelengkap - PPKA Umum Kolek 1', 'Komponen modal', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiModalPelengkap', 'Bobot 0.5%'),
            $this->row('KPMM', 'Modal Pelengkap - PPKA Umum Kolek 2', 'Komponen modal', 'Semua tanggal', '-', 'LoanOutstanding (non-COA)', 'BranchOffice::konsolidasiModalPelengkap', 'Bobot 3%'),
            $this->row('KPMM', 'Modal Pelengkap - ABA threshold', 'Komponen modal', 'Semua tanggal', 'prefix 111*, 112*, 113* (panjang akun 7)', 'SaldoNeraca', 'BranchOffice::konsolidasiModalPelengkap', 'Excess di atas 2.000.000.000 per akun x 0.5%'),
            $this->row('KPMM', 'ATMR - GL Weighted', 'Penyebut KPMM', 'Semua tanggal', '100, 110, 150, 197, 196, 195, 194, 193, 192, 191, 184, 183, 182, 181, 171, 163, 164, 162, 161, 132, 131, 129', 'SaldoNeraca', 'BranchOffice::konsolidasiATMR', 'Bobot sesuai map GLWeights di method'),
            $this->row('KPMM', 'ATMR - Loan buckets', 'Penyebut KPMM', 'Semua tanggal', '-', 'LoanOutstanding/collateral/customer (non-COA)', 'BranchOffice::konsolidasiATMR', 'Bucket 100%, 30%, 50%, 70%, 100% sesuai aturan method'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function row(
        string $rasio,
        string $komponen,
        string $peran,
        string $periode,
        string $kodeCoa,
        string $sumberData,
        string $sumberKode,
        string $catatan
    ): array {
        return [
            $rasio,
            $komponen,
            $peran,
            $periode,
            $kodeCoa,
            $sumberData,
            $sumberKode,
            $catatan,
        ];
    }
}
