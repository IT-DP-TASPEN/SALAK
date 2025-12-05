<?php

namespace App\Console\Commands;

use App\Models\LoanCollateralList;
use App\Services\Fincloud;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchLoanCollateralList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-loan-collateral-list {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch loan collateral list from Fincloud for all branches as of a specific date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $date = Carbon::parse($this->argument('date') ?? now()->subDay()->format('Y-m-d'));

            $fincloud = new Fincloud();
            $fincloud->login(
                username: config('services.fincloud.username'),
                password: config('services.fincloud.password'),
                role: config('services.fincloud.role'),
                location: config('services.fincloud.location'),
            );

            $this->info("Fetching loan collateral list as of {$date->format('Y-m-d')}");

            $report = ltrim($fincloud->inquiryLoanCollateralListReport($date), "\xEF\xBB\xBF");
            $lines  = explode("\n", trim($report));
            $lineNo = 1;
            $header = array_map(
                static::mapHeaderToField(...),
                str_getcsv(array_shift($lines), separator: '|'),
            );

            $batch      = [];
            $batchSize  = 100;
            $totalSaved = 0;

            foreach ($lines as $line) {
                $lineNo++;

                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $columns = str_getcsv($line, separator: '|');
                if (count($columns) !== count($header)) {
                    $this->warn("Line {$lineNo} dilewati: jumlah kolom tidak cocok dengan header.");
                    continue;
                }

                $data = array_combine($header, $columns);
                $data = array_map(fn($value) => $value ? trim($value) : null, $data);
                $data['credit_limit'] = static::parseCurrency($data['credit_limit']);
                $data['loan_principal'] = static::parseCurrency($data['loan_principal']);
                $data['outstanding'] = static::parseCurrency($data['outstanding']);
                $data['collateral_real_value'] = $data['collateral_real_value'] ? static::parseCurrency($data['collateral_real_value']) : null;
                $data['collateral_market_value'] = $data['collateral_market_value'] ? static::parseCurrency($data['collateral_market_value']) : null;

                $batch[] = $data;
                if (count($batch) >= $batchSize) {
                    // Simpan batch ke database
                    LoanCollateralList::upsert($batch, ['loan_account_number'], array_keys($batch[0]));
                    $totalSaved += count($batch);
                    $this->info("Saved {$totalSaved} records so far...");
                    $batch = [];
                }
            }

            // Simpan sisa batch ke database
            if (count($batch) > 0) {
                LoanCollateralList::upsert($batch, ['loan_account_number'], array_keys($batch[0]));
                $totalSaved += count($batch);
                $this->info("Saved a total of {$totalSaved} records.");
            }
        } catch (\Exception $e) {
            $this->error("Error : " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info("Finished fetching loan collateral list.");
        return Command::SUCCESS;
    }

    private static function mapHeaderToField(string $header): string
    {
        return static::sanitizeHeader($header);
    }

    private static function sanitizeHeader(string $header): string
    {
        $header = trim($header);
        $header = strtolower($header);
        $header = str_replace(' ', '_', $header);
        $header = str_replace('"', '', $header);
        return $header;
    }

    private static function parseCurrency(string $value): float
    {
        if (empty($value)) {
            return 0.0;
        }
        $value = str_replace('<', '', $value);
        $value = str_replace('>', '', $value);
        $value = str_replace(',', '', $value);
        return (float) $value;
    }
}
