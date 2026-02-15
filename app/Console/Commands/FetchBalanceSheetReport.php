<?php

namespace App\Console\Commands;

use App\Models\SaldoNeraca;
use App\Services\Fincloud;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchBalanceSheetReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-balance-sheet-report {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch balance sheet report from Fincloud for all branches as of a specific date';

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

            $branches = $fincloud->getBranches();
            foreach ($branches as $branch) {
                $this->info("Fetching balance sheet report for branch: {$branch} as of {$date->format('Y-m-d')}");
                $reportData = $fincloud->inquiryBalanceSheetReport($date, $branch);

                // remove BOM
                $reportData = ltrim($reportData, "\xEF\xBB\xBF");
                // split on new line and parse CSV
                $lines = explode("\n", trim($reportData));
                $headers = array_map(
                    static::mapHeaderToField(...),
                    str_getcsv(array_shift($lines), separator: '|'),
                );
                $records = array_map(function ($line) use ($headers, $date, $branch) {
                    $res = array_combine($headers, str_getcsv($line, separator: '|'));
                    $res['cabang'] = $branch;
                    $res['tanggal'] = $date->format('Y-m-d');
                    $res['saldoawal'] = static::parseCurrency($res['saldoawal']);
                    $res['mutasidebit'] = static::parseCurrency($res['mutasidebit']);
                    $res['mutasikredit'] = static::parseCurrency($res['mutasikredit']);
                    $res['saldoakhir'] = static::parseCurrency($res['saldoakhir']);
                    return $res;
                }, $lines);

                foreach (array_chunk($records, 100) as $chunk) {
                    SaldoNeraca::upsert(
                        $chunk,
                        ['cabang', 'tanggal', 'noakun'],
                        ['namaakun', 'saldoawal', 'mutasidebit', 'mutasikredit', 'saldoakhir'],
                    );
                }
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('Successfully fetched balance sheet report.');
        return Command::SUCCESS;
    }

    private static function mapHeaderToField(string $header): string
    {
        return match (trim(strtolower($header))) {
            'branch' => 'cabang',
            'coa no' => 'noakun',
            'chart of account' => 'namaakun',
            'beginning balance' => 'saldoawal',
            'debit transaction' => 'mutasidebit',
            'credit transaction' => 'mutasikredit',
            'last balance' => 'saldoakhir',
            default => strtolower(str_replace(' ', '_', $header)),
        };
    }

    private static function parseCurrency(string $value): float
    {
        if (empty($value)) {
            return 0.0;
        }
        $sign = 1;
        $negativeIndicators = [
            '(' => ')',
            '<' => '>',
            '-' => '',
        ];
        foreach ($negativeIndicators as $open => $close) {
            if (str_starts_with($value, $open) && str_ends_with($value, $close)) {
                $sign = -1;
                $value = str_replace([$open, $close], '', $value);
                break;
            }
        }
        $value = str_replace('<', '', $value);
        $value = str_replace('>', '', $value);
        $value = str_replace(',', '', $value);
        return (float) $value * $sign;
    }
}
