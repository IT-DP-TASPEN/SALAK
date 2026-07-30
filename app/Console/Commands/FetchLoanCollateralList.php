<?php

namespace App\Console\Commands;

use App\Models\LoanCollateralList;
use App\Services\Fincloud;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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

            $fincloud = app(Fincloud::class);
            $fincloud->login(
                username: config('services.fincloud.username'),
                password: config('services.fincloud.password'),
                role: config('services.fincloud.role'),
                location: config('services.fincloud.location'),
            );

            $this->info("Fetching loan collateral list as of {$date->format('Y-m-d')}");

            $report = ltrim($fincloud->inquiryLoanCollateralListReport($date), "\xEF\xBB\xBF");
            $lines = explode("\n", trim($report));
            if ($lines === ['']) {
                throw new \RuntimeException('Collateral report is empty.');
            }

            $lineNo = 1;
            $header = array_map(
                static::mapHeaderToField(...),
                str_getcsv(array_shift($lines), separator: '|'),
            );

            $missingHeaders = array_diff(['loan_acc_no', 'collateral_type'], $header);
            if ($missingHeaders !== []) {
                throw new \RuntimeException(
                    'Missing required collateral headers: '.implode(', ', $missingHeaders)
                );
            }

            $collaterals = [];
            $validRows = 0;

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
                $loanAccount = static::cleanValue($data['loan_acc_no']);
                if ($loanAccount === null) {
                    $this->warn("Line {$lineNo} dilewati: nomor rekening pinjaman kosong.");

                    continue;
                }

                $validRows++;
                $collateralType = static::cleanValue($data['collateral_type']);
                if (
                    $collateralType === null
                    || (
                        stripos($collateralType, 'land') === false
                        && stripos($collateralType, 'building') === false
                    )
                ) {
                    continue;
                }

                $collaterals[$loanAccount] = [
                    'fetch_date' => $date->toDateString(),
                    'loan_acc_no' => $loanAccount,
                    'collateral_type' => $collateralType,
                ];
            }

            if ($validRows === 0) {
                throw new \RuntimeException('Collateral report contains no valid rows.');
            }

            DB::transaction(function () use ($collaterals, $date) {
                LoanCollateralList::query()
                    ->where('fetch_date', $date->toDateString())
                    ->delete();

                foreach (array_chunk(array_values($collaterals), 1000) as $batch) {
                    LoanCollateralList::query()->insert($batch);
                }
            });

            $this->info('Saved '.count($collaterals).' unique land/building collateral accounts.');
        } catch (\Throwable $e) {
            $this->error('Error : '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Finished fetching loan collateral list.');

        return Command::SUCCESS;
    }

    private static function mapHeaderToField(string $header): string
    {
        return static::sanitizeHeader($header);
    }

    private static function sanitizeHeader(string $header): string
    {
        return str_replace(' ', '_', strtolower(trim($header, " \t\n\r\0\x0B\xEF\xBB\xBF\"")));
    }

    private static function cleanValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
