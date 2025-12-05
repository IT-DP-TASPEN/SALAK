<?php

namespace App\Console\Commands;

use App\Models\LoanOutstanding;
use App\Services\Fincloud;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchLoanOutstandingReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-loan-outstanding-report {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch loan outstanding report from Fincloud for all branches as of a specific date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $date = Carbon::parse($this->argument('date') ?? now()->format('Y-m-d'));

            $fincloud = new Fincloud();
            $fincloud->login(
                username: config('services.fincloud.username'),
                password: config('services.fincloud.password'),
                role: config('services.fincloud.role'),
                location: config('services.fincloud.location'),
            );

            $header = null;
            $i = 0;
            $upsertData = [];

            foreach ($fincloud->inquiryDetailOutstandingReport($date) as $line) {
                // $this->line($line, 'info');
                if ($i++ === 0) {
                    $header = array_map(static::mapHeaderToField(...), str_getcsv($line, separator: '|'));
                    continue;
                }

                $upsertData[] = array_combine($header, str_getcsv($line, separator: '|'));
                if ($i % 200 === 0) {
                    $records = array_map(function ($data) {
                        $data['loan_interest_rate'];
                    }, $upsertData);
                    $this->info("Upserted " . count($upsertData) . " records to database.");
                    $upsertData = [];
                }
            }
        } catch (\Exception $e) {
            $this->error("Error fetching loan outstanding report: " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('Successfully fetched loan outstanding report.');
        return Command::SUCCESS;
    }

    private static function mapHeaderToField(string $header): string
    {
        return match (trim(strtolower($header))) {
            'param_tanggal' => 'loan_date_params',
            'cabang_rekening' => 'loan_branch_office',
            'produk' => 'loan_product',
            'no_rekening' => 'loan_account',
            'no_cif' => 'loan_cif',
            'no_cif_alt' => 'loan_alt_account',
            'no_pk' => 'loan_agreement_no',
            'periode_mulai' => 'loan_start_date',
            'periode_akhir' => 'loan_end_date',
            'suku_bunga' => 'loan_interest_rate',
            'nilai_angsuran' => 'loan_installment_loans',
            'kolektibilitas_bi' => 'loan_bi_collectability',
            'dpd' => 'loan_days_past_due',
            'currency' => 'loan_currency',
            'pokok_pinjaman' => 'loan_principal',
            'sisa_pokok_pinjaman' => 'loan_outstanding',
            'tunggakan_pokok' => 'loan_principal_arrears',
            'tunggakan_bunga' => 'loan_interest_arrears',
            'denda_tunggakan' => 'loan_penalty_arrears',
            'accrue_bunga' => 'loan_accrue_interest',
            'kode_marketing' => 'loan_marketing_code',
            default => trim(strtolower(str_replace(' ', '_', $header))),
        };
    }

    // private static function 
}
