<?php

namespace App\Console\Commands;

use App\Models\CbrCustomer;
use App\Services\Fincloud;
use Carbon\Carbon;
use Illuminate\Console\Command;

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

            $fincloud = new Fincloud();
            $fincloud->login(
                username: config('services.fincloud.username'),
                password: config('services.fincloud.password'),
                role: config('services.fincloud.role'),
                location: config('services.fincloud.location'),
            );

            $this->info("Fetching CBR customer report as of {$date->format('Y-m-d')}");

            $header     = null;
            $lineNo     = 0;
            $batch      = [];
            $batchSize  = 100;
            $totalSaved = 0;

            foreach ($fincloud->inquiryCbrCustomerReport($date) as $line) {
                if ($lineNo++ === 0) {
                    $header = str_getcsv($line, separator: '|');
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

                $batch[] = array_map(fn($value) => $value ? trim($value) : null, $data);
                if (count($batch) >= $batchSize) {
                    CbrCustomer::upsert(
                        $batch,
                        ['cif_no', 'management_name'],
                        array_keys($batch[0]),
                    );
                    $totalSaved += count($batch);
                    $this->info("Upserted {$totalSaved} records to database.");
                    $batch = [];
                }
            }

            // Insert sisa batch
            if (count($batch) > 0) {
                CbrCustomer::upsert(
                    $batch,
                    ['cif_no', 'management_name'],
                    array_keys($batch[0]),
                );
                $totalSaved += count($batch);
                $this->info("Upserted total {$totalSaved} records to database.");
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info("Successfully fetched CBR customer report.");
        return Command::SUCCESS;
    }
}
