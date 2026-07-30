<?php

namespace App\Console\Commands;

use App\Models\CbrCustomer;
use App\Services\Fincloud;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FetchCbrCustomerReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-cbr-customer-report {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch CBR customer report from Fincloud';

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

            $this->info("Fetching CBR customer report as of {$date->format('Y-m-d')}");

            $header = null;
            $lineNo = 0;
            $customers = [];

            foreach ($fincloud->inquiryCbrCustomerReport($date) as $line) {
                if ($lineNo++ === 0) {
                    $header = array_map(
                        static::sanitizeHeader(...),
                        str_getcsv($line, separator: '|'),
                    );

                    $missingHeaders = array_diff(
                        ['cif_no', 'owner_group', 'debtor_group'],
                        $header,
                    );
                    if ($missingHeaders !== []) {
                        throw new \RuntimeException(
                            'Missing required CBR headers: '.implode(', ', $missingHeaders)
                        );
                    }

                    continue;
                }

                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $columns = str_getcsv($line, separator: '|');

                // Safety: jumlah kolom harus sama dengan header
                if (count($columns) !== count($header)) {
                    $this->warn("Line {$lineNo} dilewati: jumlah kolom tidak cocok dengan header.");

                    continue;
                }

                $data = array_combine($header, $columns);
                $cifNo = static::cleanValue($data['cif_no']);
                if ($cifNo === null) {
                    $this->warn("Line {$lineNo} dilewati: CIF kosong.");

                    continue;
                }

                // Last row wins, matching the previous upsert behavior for repeated CIFs.
                $customers[$cifNo] = [
                    'fetch_date' => $date->toDateString(),
                    'cif_no' => $cifNo,
                    'owner_group' => static::cleanValue($data['owner_group']),
                    'debtor_group' => static::cleanValue($data['debtor_group']),
                ];
            }

            if ($customers === []) {
                throw new \RuntimeException('CBR report contains no valid customer rows.');
            }

            DB::transaction(function () use ($customers, $date) {
                CbrCustomer::query()
                    ->where('fetch_date', $date->toDateString())
                    ->delete();

                foreach (array_chunk(array_values($customers), 1000) as $batch) {
                    CbrCustomer::query()->insert($batch);
                }
            });

            $this->info('Saved '.count($customers).' unique CBR customers.');
        } catch (\Throwable $e) {
            $this->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Successfully fetched CBR customer report.');

        return Command::SUCCESS;
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
