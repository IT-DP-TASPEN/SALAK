<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FetchAtmrDataFromMso extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-atmr-data-from-mso';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch data needed for ATMR from MSO';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $results = DB::connection('mso-backup')
            ->table('data_kredit_master')
            ->join('data_nasabah_master', 'kre_nasabah', '=', 'nasabah_id')
            ->join('data_kredit_pelengkap', 'pelengkap_rekening', '=', 'kre_rekening')
            ->select([
                'kre_rekening',
                'kre_nasabah',
                'pelengkap_bi_goldebitur',
                'pelengkap_bi_jenisusaha',
            ])
            ->where('kre_status', 2) // only active loans
            ->get();

        $bar = $this->output->createProgressBar($results->count());
        $bar->start();
        foreach ($results as $row) {
            DB::table('mso_loan_atmrs')->updateOrInsert(
                ['loan_account' => str_replace('.', '', $row->kre_rekening)],
                [
                    'loan_cif'               => str_replace('.', '', $row->kre_nasabah),
                    'loan_golongan_debitur'  => $row->pelengkap_bi_goldebitur,
                    'loan_jenis_usaha'       => $row->pelengkap_bi_jenisusaha,
                    'updated_at'             => now(),
                ],
            );
            $bar->advance();
        }
        $bar->finish();

        $this->info("\nDone fetching ATMR data from MSO.");
    }
}
