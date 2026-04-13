<?php

namespace Tests\Unit;

use App\Services\NeracaReportService;
use Tests\Concerns\InteractsWithNeracaTestEnvironment;
use Tests\TestCase;

class NeracaReportServiceTest extends TestCase
{
    use InteractsWithNeracaTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootNeracaTestEnvironment();
    }

    protected function tearDown(): void
    {
        $this->destroyNeracaTestEnvironment();

        parent::tearDown();
    }

    public function test_build_uses_exact_match_sums_multi_coa_rows_and_calculates_section_totals_from_rows(): void
    {
        $this->createBranchOffice(1, '001', 'Cabang 1');

        $this->createSaldoNeraca('2026-01-07', '001', '101', 100);
        $this->createSaldoNeraca('2026-01-07', '001', '1011000', 999);
        $this->createSaldoNeraca('2026-01-07', '001', '111', 10);
        $this->createSaldoNeraca('2026-01-07', '001', '112', 20);
        $this->createSaldoNeraca('2026-01-07', '001', '113', 30);
        $this->createSaldoNeraca('2026-01-07', '001', '114', 5);
        $this->createSaldoNeraca('2026-01-07', '001', '121', 200);
        $this->createSaldoNeraca('2026-01-07', '001', '122', 300);
        $this->createSaldoNeraca('2026-01-07', '001', '127', 40);
        $this->createSaldoNeraca('2026-01-07', '001', '151', 777);
        $this->createSaldoNeraca('2026-01-07', '001', '1511000', 80);
        $this->createSaldoNeraca('2026-01-07', '001', '261', -500);
        $this->createSaldoNeraca('2026-01-07', '001', '301', -1000);
        $this->createSaldoNeraca('2026-01-07', '001', '302', -200);
        $this->createSaldoNeraca('2026-01-07', '001', '323', -300);
        $this->createSaldoNeraca('2026-01-07', '001', '1', 999999);
        $this->createSaldoNeraca('2026-01-07', '001', '2', -888888);
        $this->createSaldoNeraca('2026-01-07', '001', '3', -777777);

        $report = app(NeracaReportService::class)->build('2026-01-07', '001');

        $this->assertSame(100.0, $this->rowValue($report['assets']['rows'], '1101010000'));
        $this->assertSame(0.0, $this->rowValue($report['assets']['rows'], '1101020000'));
        $this->assertSame(60.0, $this->rowValue($report['assets']['rows'], '1103010000'));
        $this->assertSame(80.0, $this->rowValue($report['assets']['rows'], '1202010000'));
        $this->assertSame(785.0, $this->rowValue($report['assets']['rows'], '1000000000'));

        $this->assertSame(500.0, $this->rowValue($report['liabilities']['rows'], '2201010000'));
        $this->assertSame(500.0, $this->rowValue($report['liabilities']['rows'], '2000000000'));

        $this->assertSame(1000.0, $this->rowValue($report['equity']['rows'], '3101010000'));
        $this->assertSame(200.0, $this->rowValue($report['equity']['rows'], '3101020000'));
        $this->assertSame(300.0, $this->rowValue($report['equity']['rows'], '3105020000'));
        $this->assertSame(1500.0, $this->rowValue($report['equity']['rows'], '3000000000'));
    }

    public function test_build_returns_consolidated_balances_when_branch_code_is_blank(): void
    {
        $this->createBranchOffice(1, '001', 'Cabang 1');
        $this->createBranchOffice(2, '002', 'Cabang 2');

        $this->createSaldoNeraca('2026-01-07', '001', '101', 150);
        $this->createSaldoNeraca('2026-01-07', '002', '101', 250);

        $report = app(NeracaReportService::class)->build('2026-01-07', null);

        $this->assertSame(400.0, $this->rowValue($report['assets']['rows'], '1101010000'));
        $this->assertSame(400.0, $this->rowValue($report['assets']['rows'], '1000000000'));
    }

    /**
     * @param  array<int, array{pos: string, description: string, value: float, is_total: bool}>  $rows
     */
    private function rowValue(array $rows, string $pos): float
    {
        foreach ($rows as $row) {
            if ($row['pos'] === $pos) {
                return $row['value'];
            }
        }

        $this->fail("Row with pos [{$pos}] was not found.");
    }
}
