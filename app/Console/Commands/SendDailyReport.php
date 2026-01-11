<?php

namespace App\Console\Commands;

use App\Models\BranchOffice;
use App\Models\LoanOutstanding;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-daily-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily report emails to stakeholders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $asOf = Carbon::today()->format('Y-m-d');
        $kas = BranchOffice::konsolidasiSaldoKas($asOf);
        $tab = array_sum(BranchOffice::saldoNeraca2(['112'], null, $asOf));
        $dep = array_sum(BranchOffice::saldoNeraca2(['113'], null, $asOf));
        $kre = array_sum(BranchOffice::saldoNeraca2(['121'], null, $asOf));
        $krePerKolek = LoanOutstanding::query()
            ->selectRaw('loan_bi_collectability, SUM(loan_outstanding) as total_outstanding')
            ->where('loan_date_params', $asOf)
            ->groupBy('loan_bi_collectability')
            ->orderBy('loan_bi_collectability')
            ->pluck('total_outstanding', 'loan_bi_collectability')
            ->toArray();
        $aba = array_sum(BranchOffice::saldoNeraca2(['110'], null, $asOf));
        $asset = array_sum(BranchOffice::saldoNeraca2(['1'], null, $asOf));
        $npl = BranchOffice::konsolidasiNPL($asOf);

        $msg = "Daily Report as of {$asOf}:\n";
        $msg .= "Kas: " . self::formatAmount($kas) . "\n";
        $msg .= "Tabungan: " . self::formatAmount($tab) . "\n";
        $msg .= "Deposito: " . self::formatAmount($dep) . "\n";
        $msg .= "Kredit: " . self::formatAmount($kre) . "\n";
        foreach ($krePerKolek as $kolek => $amount) {
            $msg .= "  - " . self::mapKolekNumberToLetter($kolek) . ": " . self::formatAmount($amount) . "\n";
        }
        $msg .= "ABA: " . self::formatAmount($aba) . "\n";
        $msg .= "Total Asset: " . self::formatAmount($asset) . "\n";
        $msg .= "NPL: " . sprintf("%.2f%%", $npl) . "\n";

        print_r($msg);
    }

    private static function formatAmount($amount): string
    {
        return "Rp. " . number_format($amount, 2, '.', ',');
    }

    private static function mapKolekNumberToLetter($amount): string
    {
        return match ($amount) {
            1 => 'Lancar',
            2 => 'Dalam Perhatian Khusus',
            3 => 'Kurang Lancar',
            4 => 'Diragukan',
            5 => 'Macet',
            default => 'Unknown',
        };
    }
}
