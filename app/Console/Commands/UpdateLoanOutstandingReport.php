<?php

namespace App\Console\Commands;

use App\Models\LoanOutstanding;
use App\Services\Fincloud;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class UpdateLoanOutstandingReport extends Command
{
    protected $signature = 'app:update-loan-outstanding-report';
    protected $description = 'Update the loan outstanding data from General Report';

    private const CHUNK_SIZE = 200;

    public function handle()
    {
        DB::disableQueryLog();

        try {
            $fincloud = new Fincloud();
            $fincloud->login(
                username: config('services.fincloud.username'),
                password: config('services.fincloud.password'),
                role: config('services.fincloud.role'),
                location: config('services.fincloud.location'),
            );

            $raw = $fincloud->inquiryLoanOutstandingFromGeneralReport();

            // remove UTF-8 BOM only if it’s at the beginning
            $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;

            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $raw);
            rewind($stream);

            $headerLine = fgets($stream);
            if ($headerLine === false) {
                $this->error('Empty report: no header line.');
                return Command::FAILURE;
            }

            $headerLine = rtrim($headerLine, "\r\n");
            $headerCells = str_getcsv($headerLine, separator: '|');

            // Keep only mapped headers (index => fieldName), so we can ignore unknown columns cleanly.
            $mappedHeaders = [];
            foreach ($headerCells as $i => $h) {
                $field = self::mapHeaderToField($h);
                if ($field !== null) {
                    $mappedHeaders[$i] = $field;
                }
            }

            if (empty($mappedHeaders)) {
                $this->error('No recognized headers. Header row: ' . $headerLine);
                return Command::FAILURE;
            }

            $decimalFields = array_flip([
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
            ]);

            $tableName = (new LoanOutstanding())->getTable();

            $uniqueBy = ['loan_date_params', 'loan_account'];

            $buffer = [];
            $processed = 0;
            $skippedNoKey = 0;

            while (($line = fgets($stream)) !== false) {
                $line = rtrim($line, "\r\n");
                if ($line === '') {
                    continue;
                }

                $cells = str_getcsv($line, separator: '|');

                $row = [];
                foreach ($mappedHeaders as $idx => $fieldName) {
                    $value = $cells[$idx] ?? null;

                    if (is_string($value)) {
                        $value = trim($value);
                        if ($value === '') $value = null;
                    }

                    if (isset($decimalFields[$fieldName])) {
                        $value = self::normalizeDecimal($value);
                    }

                    if ($fieldName === 'loan_branch_office') {
                        $value = explode('-', (string) $value)[0];
                    }

                    $row[$fieldName] = $value;
                }

                // Must have key(s) for upsert/update
                $missingKey = false;
                foreach ($uniqueBy as $k) {
                    if (!isset($row[$k]) || $row[$k] === '') {
                        $missingKey = true;
                        break;
                    }
                }
                if ($missingKey) {
                    $skippedNoKey++;
                    continue;
                }

                // Deduplicate inside the chunk so we don't pass duplicates in one upsert payload
                $dedupeKey = implode('|', array_map(fn($k) => (string) $row[$k], $uniqueBy));
                $buffer[$dedupeKey] = $row;

                $processed++;

                if (count($buffer) >= self::CHUNK_SIZE) {
                    $this->flushChunk($tableName, array_values($buffer), $uniqueBy);
                    $buffer = [];
                }
            }

            if (!empty($buffer)) {
                $this->flushChunk($tableName, array_values($buffer), $uniqueBy);
            }

            fclose($stream);

            $this->info("Done. Processed={$processed}, Skipped(no-key)={$skippedNoKey}, UniqueBy=[" . implode(',', $uniqueBy) . "]");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function flushChunk(string $tableName, array $rows, array $uniqueBy): void
    {
        if (empty($rows)) return;

        // Update all columns except the unique keys
        $updateColumns = array_values(array_diff(array_keys($rows[0]), $uniqueBy));

        DB::transaction(function () use ($tableName, $rows, $uniqueBy, $updateColumns) {
            try {
                DB::table($tableName)->upsert($rows, $uniqueBy, $updateColumns);
            } catch (QueryException $e) {
                // Fallback: still chunked, but per-row updateOrInsert (slower).
                // This is here in case your DB lacks the required unique index for upsert to behave correctly.
                foreach ($rows as $row) {
                    $where = [];
                    foreach ($uniqueBy as $k) {
                        $where[$k] = $row[$k];
                    }
                    DB::table($tableName)->updateOrInsert($where, $row);
                }
            }
        });
    }

    private static function normalizeDecimal(?string $s): ?string
    {
        if ($s === null) return null;

        $s = trim($s);
        if ($s === '' || $s === '-') return null;

        $s = str_replace('%', '', $s);

        // Keep only digits, separators, minus
        $s = preg_replace('/[^\d,\.\-]/', '', $s) ?? '';
        if ($s === '' || $s === '-') return null;

        // Remove non-leading minus signs (e.g. "1-234" -> "1234")
        $s = preg_replace('/(?!^)-/', '', $s) ?? $s;

        $lastComma = strrpos($s, ',');
        $lastDot   = strrpos($s, '.');

        if ($lastComma !== false && $lastDot === false) {
            // "123,45" -> "123.45"
            $s = str_replace(',', '.', $s);
        } elseif ($lastComma !== false && $lastDot !== false) {
            // Decide decimal separator by last occurrence
            if ($lastComma > $lastDot) {
                // "1.234,56" -> "1234.56"
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                // "1,234.56" -> "1234.56"
                $s = str_replace(',', '', $s);
            }
        } else {
            // Only dots: could be thousands separators ("1.234.567") or decimal ("1234.56").
            // If it matches grouped thousands, strip dots.
            if (preg_match('/^\-?\d{1,3}(\.\d{3})+$/', $s)) {
                $s = str_replace('.', '', $s);
            }
        }

        // If still multiple dots, keep the last as decimal separator
        if (substr_count($s, '.') > 1) {
            $parts = explode('.', $s);
            $last = array_pop($parts);
            $s = implode('', $parts) . '.' . $last;
        }

        $s = trim($s);
        return ($s === '' || $s === '-') ? null : $s;
    }

    private static function mapHeaderToField(string $header): ?string
    {
        return match (trim(strtolower($header))) {
            'date_params' => 'loan_date_params',
            'branch_code' => 'loan_branch_office',
            'product_id' => 'loan_product',
            'loan_account_no' => 'loan_account',
            'customer_name' => 'loan_customer',
            'cif_no' => 'loan_cif',
            'cif_alt_no' => 'loan_cif_alt',
            'loan_alt_no' => 'loan_alt_account',
            'credit_limit_no' => 'loan_credit_limit_no',
            'loan_agreement_no' => 'loan_agreement_no',
            'start_date' => 'loan_start_date',
            'end_date' => 'loan_end_date',
            'interest_rate' => 'loan_interest_rate',
            'installments_loan' => 'loan_installment_loans',
            'principal_installments_loan' => 'loan_installment_principal',
            'interest_installments_loan' => 'loan_installment_interest',
            'bi_collectability' => 'loan_bi_collectability',
            'day_past_due' => 'loan_days_past_due',
            'currency' => 'loan_currency',
            'loan_principal' => 'loan_principal',
            'loan_outstanding' => 'loan_outstanding',
            'principal_arrears' => 'loan_principal_arrears',
            'interest_arrears' => 'loan_interest_arrears',
            'penalty_arrears' => 'loan_penalty_arrears',
            'accrue_interest' => 'loan_accrue_interest',
            'marketing_id' => 'loan_marketing_code',
            default => null,
        };
    }
}
