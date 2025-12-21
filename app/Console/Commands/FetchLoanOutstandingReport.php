<?php

namespace App\Console\Commands;

use App\Models\LoanOutstanding;
use App\Services\Fincloud;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchLoanOutstandingReport extends Command
{
    protected $signature = 'app:fetch-loan-outstanding-report {date?}';
    protected $description = 'Fetch loan outstanding report from Fincloud for all branches as of a specific date';

    public function handle()
    {
        try {
            $date = Carbon::parse($this->argument('date') ?? now()->format('Y-m-d'))->startOfDay();

            $fincloud = new Fincloud();
            $fincloud->login(
                username: config('services.fincloud.username'),
                password: config('services.fincloud.password'),
                role: config('services.fincloud.role'),
                location: config('services.fincloud.location'),
            );

            $headerIndexToField = null; // [idx => fieldName]
            $upsertData = [];
            $chunkSize = 250;

            $uniqueBy = ['loan_date_params', 'loan_branch_office', 'loan_account'];

            $model = new LoanOutstanding();
            $fillable = $model->getFillable();
            $useTimestamps = method_exists($model, 'usesTimestamps') ? $model->usesTimestamps() : true;

            $i = 0;

            foreach ($fincloud->inquiryDetailOutstandingReport($date) as $line) {
                $line = rtrim((string) $line, "\r\n");
                if ($line === '') {
                    continue;
                }

                // header
                if ($i++ === 0) {
                    $rawHeader = str_getcsv($line, separator: '|');

                    // strip BOM safety on first col
                    if (isset($rawHeader[0]) && is_string($rawHeader[0])) {
                        $rawHeader[0] = ltrim($rawHeader[0], "\xEF\xBB\xBF");
                    }

                    $headerIndexToField = $this->buildHeaderIndexMap($rawHeader);

                    // minimal required fields
                    $required = ['loan_account'];
                    foreach ($required as $req) {
                        if (!in_array($req, $headerIndexToField, true)) {
                            throw new \RuntimeException("Missing required column in CSV header (after mapping): {$req}");
                        }
                    }

                    continue;
                }

                if ($headerIndexToField === null) {
                    throw new \RuntimeException('Header not initialized');
                }

                $cols = str_getcsv($line, separator: '|');

                // basic sanity: if row is shorter than max index we need, skip
                $maxIdx = empty($headerIndexToField) ? -1 : max(array_keys($headerIndexToField));
                if (count($cols) <= $maxIdx) {
                    $this->warn("Skip row: column count mismatch (got " . count($cols) . ", need > {$maxIdx})");
                    continue;
                }

                // build row only from mapped fields (unknown headers are skipped)
                $row = [];
                foreach ($headerIndexToField as $idx => $field) {
                    $row[$field] = $cols[$idx] ?? null;
                }

                // defaults (kalau CSV ga punya / ada yang kosong)
                $row['loan_date_params'] ??= $date->toDateString();
                $row['loan_branch_office'] = substr($row['loan_branch_office'] ?? '', 0, 3);

                $row = $this->normalizeRow($row);

                // restrict inserted columns to "safe" ones
                $row = $this->restrictToAllowedColumns($row, $fillable, $uniqueBy, $useTimestamps);

                // must-have keys for upsert
                foreach ($uniqueBy as $key) {
                    if (!array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
                        // kalau uniqueBy lu cuma loan_account, ini tetep aman
                        $this->warn("Skip row: missing key field '{$key}'");
                        continue 2;
                    }
                }

                $upsertData[] = $row;

                if (count($upsertData) >= $chunkSize) {
                    $this->flushUpsert($upsertData, $uniqueBy, $fillable, $useTimestamps);
                    $this->info("Upserted " . count($upsertData) . " records to database.");
                    $upsertData = [];
                }
            }

            // flush remaining
            if (count($upsertData) > 0) {
                $this->flushUpsert($upsertData, $uniqueBy, $fillable, $useTimestamps);
                $this->info("Upserted " . count($upsertData) . " records to database.");
            }
        } catch (\Throwable $e) {
            $this->error("Error fetching loan outstanding report: " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('Successfully fetched loan outstanding report.');
        return Command::SUCCESS;
    }

    /**
     * Build [index => fieldName] mapping from raw header columns.
     * - Unknown header -> skipped
     * - Detect duplicate mapped fields (data corruption risk)
     */
    private function buildHeaderIndexMap(array $rawHeader): array
    {
        $map = [];

        foreach ($rawHeader as $idx => $h) {
            $field = self::mapHeaderToField((string) $h);
            if ($field === null) {
                continue;
            }
            $map[(int) $idx] = $field;
        }

        // detect duplicates
        $counts = array_count_values(array_values($map));
        $dupes = array_keys(array_filter($counts, fn($c) => $c > 1));
        if (!empty($dupes)) {
            throw new \RuntimeException('Duplicate mapped fields in header: ' . implode(', ', $dupes));
        }

        return $map;
    }

    private function flushUpsert(array $rows, array $uniqueBy, array $fillable, bool $useTimestamps): void
    {
        if (empty($rows)) return;

        if ($useTimestamps) {
            $now = now();
            foreach ($rows as &$r) {
                $r['updated_at'] = $now;
                $r['created_at'] ??= $now;
            }
            unset($r);
        }

        // update columns: prefer fillable, but exclude unique keys + created_at
        $updateColumns = $fillable;
        if (empty($updateColumns)) {
            $updateColumns = array_keys($rows[0]); // fallback
        }
        $updateColumns = array_values(array_diff($updateColumns, $uniqueBy, ['created_at']));

        LoanOutstanding::upsert($rows, $uniqueBy, $updateColumns);
    }

    private function restrictToAllowedColumns(array $row, array $fillable, array $uniqueBy, bool $useTimestamps): array
    {
        // If fillable is empty (guarded = [] style), we can’t safely filter—just return row.
        if (empty($fillable)) {
            return $row;
        }

        $allowed = array_unique(array_merge($fillable, $uniqueBy));

        if ($useTimestamps) {
            $allowed[] = 'created_at';
            $allowed[] = 'updated_at';
        }

        return array_intersect_key($row, array_flip($allowed));
    }

    private function normalizeRow(array $row): array
    {
        // trim + empty string -> null
        foreach ($row as $k => $v) {
            if (is_string($v)) {
                $v = trim($v);
                $row[$k] = ($v === '' ? null : $v);
            }
        }

        // integers
        foreach (['loan_bi_collectability', 'loan_days_past_due'] as $f) {
            if (array_key_exists($f, $row) && $row[$f] !== null) {
                $row[$f] = (int) preg_replace('/[^\d\-]/', '', (string) $row[$f]);
            }
        }

        // decimals (keep as string so DB decimal keeps precision)
        foreach (
            [
                'loan_interest_rate',
                'loan_installment_loans',
                'loan_installment_principal',
                'loan_installment_interest',
                'loan_principal',
                'loan_outstanding',
                'loan_principal_arrears',
                'loan_interest_arrears',
                'loan_penalty_arrears',
                'loan_accrue_interest',
            ] as $f
        ) {
            if (array_key_exists($f, $row) && $row[$f] !== null) {
                $row[$f] = $this->normalizeDecimal((string) $row[$f]);
            }
        }

        // dates: just trim; if you need strict parse, tighten here
        foreach (['loan_date_params', 'loan_start_date', 'loan_end_date'] as $f) {
            if (array_key_exists($f, $row) && is_string($row[$f]) && $row[$f] !== null) {
                // change DD/MM/YYYY to YYYY-MM-DD
                $d = trim($row[$f]);
                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $d, $matches)) {
                    $d = sprintf('%s-%s-%s', $matches[3], $matches[2], $matches[1]);
                }
                $row[$f] = $d;
            }
        }

        return $row;
    }

    private function normalizeDecimal(string $s): ?string
    {
        $s = trim($s);
        if ($s === '' || $s === '-') return null;

        $s = str_replace('%', '', $s);
        $s = preg_replace('/[^\d,\.\-]/', '', $s);

        $lastComma = strrpos($s, ',');
        $lastDot   = strrpos($s, '.');

        if ($lastComma !== false && $lastDot === false) {
            // "123,45" -> "123.45"
            $s = str_replace(',', '.', $s);
        } elseif ($lastComma !== false && $lastDot !== false) {
            // decide decimal separator by last occurrence
            if ($lastComma > $lastDot) {
                // "1.234,56" -> "1234.56"
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                // "1,234.56" -> "1234.56"
                $s = str_replace(',', '', $s);
            }
        }

        return $s;
    }

    private static function mapHeaderToField(string $header): ?string
    {
        return match (trim(strtolower($header))) {
            'param_tanggal' => 'loan_date_params',
            'cabang_rekening' => 'loan_branch_office',
            'produk' => 'loan_product',
            'no_rekening' => 'loan_account',
            'nama_nasabah' => 'loan_customer',
            'no_cif' => 'loan_cif',
            'no_cif_alt' => 'loan_cif_alt',
            'no_alt' => 'loan_alt_account',
            'credit_limit_no' => 'loan_credit_limit_no',
            'no_pk' => 'loan_agreement_no',
            'periode_mulai' => 'loan_start_date',
            'periode_akhir' => 'loan_end_date',
            'suku_bunga' => 'loan_interest_rate',
            'nilai_angsuran' => 'loan_installment_loans',
            'nilai_angsuran_pokok' => 'loan_installment_principal',
            'nilai_angsuran_bunga' => 'loan_installment_interest',
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
            default => null,
        };
    }
}
