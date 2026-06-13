<?php

namespace Tests\Unit;

use App\Services\LabaRugiReportService;
use Tests\Concerns\InteractsWithNeracaTestEnvironment;
use Tests\TestCase;

class LabaRugiReportServiceTest extends TestCase
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

    public function test_build_sums_mapping_actual_rows_and_calculates_laba_rugi_formulas(): void
    {
        $this->createBranchOffice(1, '001', 'Cabang 1');

        $this->createSaldoNeraca('2026-01-07', '001', '401', -1000);
        $this->createSaldoNeraca('2026-01-07', '001', '403', -200);
        $this->createSaldoNeraca('2026-01-07', '001', '407', -300);
        $this->createSaldoNeraca('2026-01-07', '001', '426', -40);
        $this->createSaldoNeraca('2026-01-07', '001', '5012101', 100);
        $this->createSaldoNeraca('2026-01-07', '001', '5022200', 50);
        $this->createSaldoNeraca('2026-01-07', '001', '578', 10);
        $this->createSaldoNeraca('2026-01-07', '001', '558', 20);
        $this->createSaldoNeraca('2026-01-07', '001', '601', -70);
        $this->createSaldoNeraca('2026-01-07', '001', '602', -30);
        $this->createSaldoNeraca('2026-01-07', '001', '690', -5);
        $this->createSaldoNeraca('2026-01-07', '001', '701', 15);
        $this->createSaldoNeraca('2026-01-07', '001', '790', 5);
        $this->createSaldoNeraca('2026-01-07', '001', '312', -40);

        $report = app(LabaRugiReportService::class)->build('2026-01-07', '001');

        $this->assertSame(0.0, $this->rowValue($report['operational']['rows'], '4101010100'));
        $this->assertSame(1000.0, $this->rowValue($report['operational']['rows'], '4101010201'));
        $this->assertSame(500.0, $this->rowValue($report['operational']['rows'], '4101010203'));
        $this->assertSame(1540.0, $this->rowValue($report['operational']['rows'], '4100000000'));

        $this->assertSame(30.0, $this->rowValue($report['operational']['rows'], '5106090000'));
        $this->assertSame(180.0, $this->rowValue($report['operational']['rows'], '5100000000'));
        $this->assertSame(1360.0, $this->rowValue($report['operational']['rows'], '3104040100'));

        $this->assertSame(105.0, $this->rowValue($report['non_operational']['rows'], '4200000000'));
        $this->assertSame(20.0, $this->rowValue($report['non_operational']['rows'], '5200000000'));
        $this->assertSame(85.0, $this->rowValue($report['non_operational']['rows'], '3104040200'));
        $this->assertSame(1445.0, $this->rowValue($report['non_operational']['rows'], '3104040300'));

        $this->assertSame(40.0, $this->rowValue($report['tax_and_comprehensive']['rows'], '5300000000'));
        $this->assertSame(20.0, $this->rowValue($report['tax_and_comprehensive']['rows'], '5400000000'));
        $this->assertSame(1385.0, $this->rowValue($report['tax_and_comprehensive']['rows'], '3104040400'));
        $this->assertSame(1385.0, $this->rowValue($report['tax_and_comprehensive']['rows'], '3104040600'));
    }

    public function test_build_returns_consolidated_balances_when_branch_code_is_blank(): void
    {
        $this->createBranchOffice(1, '001', 'Cabang 1');
        $this->createBranchOffice(2, '002', 'Cabang 2');

        $this->createSaldoNeraca('2026-01-07', '001', '401', -150);
        $this->createSaldoNeraca('2026-01-07', '002', '401', -250);

        $report = app(LabaRugiReportService::class)->build('2026-01-07', null);

        $this->assertSame(400.0, $this->rowValue($report['operational']['rows'], '4101010201'));
        $this->assertSame(400.0, $this->rowValue($report['operational']['rows'], '4100000000'));
        $this->assertSame(400.0, $this->rowValue($report['operational']['rows'], '3104040100'));
    }

    public function test_build_adds_previous_mso_values_and_recalculates_formula_rows(): void
    {
        $this->createBranchOffice(1, '001', 'Cabang 1');

        $this->createSaldoNeraca('2026-01-07', '001', '401', -150);
        $this->createSaldoNeraca('2026-01-07', '001', '5012101', 50);
        $this->createSaldoNeraca('2026-01-07', '001', '601', -20);
        $this->createSaldoNeraca('2026-01-07', '001', '701', 10);
        $this->createSaldoNeraca('2026-01-07', '001', '312', -5);
        $this->createSaldoNeraca('2026-01-07', '001', '558', 1);

        $this->createMsoMapping('4101010201', '2.112');
        $this->createMsoMapping('4101010201', '2.112.adjustment', -1);
        $this->createMsoMapping('5101010100', '2.171');
        $this->createMsoMapping('4201010000', '2.601');
        $this->createMsoMapping('5201010000', '2.701');
        $this->createMsoMapping('5300000000', '2.312');
        $this->createMsoMapping('5400000000', '2.558');

        $this->createMsoBalance('2025-01-07', '001', '2.112', 100);
        $this->createMsoBalance('2025-01-07', '001', '2.112.adjustment', 20);
        $this->createMsoBalance('2025-01-07', '001', '2.171', 40);
        $this->createMsoBalance('2025-01-07', '001', '2.601', 20);
        $this->createMsoBalance('2025-01-07', '001', '2.701', 10);
        $this->createMsoBalance('2025-01-07', '001', '2.312', 5);
        $this->createMsoBalance('2025-01-07', '001', '2.558', 1);

        $report = app(LabaRugiReportService::class)->build('2026-01-07', '001');

        $this->assertSame(150.0, $this->rowValue($report['operational']['rows'], '4101010201'));
        $this->assertSame(80.0, $this->rowPreviousValue($report['operational']['rows'], '4101010201'));
        $this->assertEqualsWithDelta(87.5, $this->rowYoyPercent($report['operational']['rows'], '4101010201'), 0.00001);

        $this->assertSame(80.0, $this->rowPreviousValue($report['operational']['rows'], '4100000000'));
        $this->assertSame(40.0, $this->rowPreviousValue($report['operational']['rows'], '5100000000'));
        $this->assertSame(40.0, $this->rowPreviousValue($report['operational']['rows'], '3104040100'));
        $this->assertEqualsWithDelta(147.5, $this->rowYoyPercent($report['operational']['rows'], '3104040100'), 0.00001);

        $this->assertSame(10.0, $this->rowPreviousValue($report['non_operational']['rows'], '3104040200'));
        $this->assertSame(50.0, $this->rowPreviousValue($report['non_operational']['rows'], '3104040300'));
        $this->assertSame(44.0, $this->rowPreviousValue($report['tax_and_comprehensive']['rows'], '3104040400'));
        $this->assertSame(44.0, $this->rowPreviousValue($report['tax_and_comprehensive']['rows'], '3104040600'));
    }

    /**
     * @param  array<int, array{pos: string, description: string, value: float, previous_value?: float, yoy_percent?: ?float, is_total: bool}>  $rows
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

    /**
     * @param  array<int, array{pos: string, description: string, value: float, previous_value?: float, yoy_percent?: ?float, is_total: bool}>  $rows
     */
    private function rowPreviousValue(array $rows, string $pos): float
    {
        foreach ($rows as $row) {
            if ($row['pos'] === $pos) {
                return (float) ($row['previous_value'] ?? 0.0);
            }
        }

        $this->fail("Row with pos [{$pos}] was not found.");
    }

    /**
     * @param  array<int, array{pos: string, description: string, value: float, previous_value?: float, yoy_percent?: ?float, is_total: bool}>  $rows
     */
    private function rowYoyPercent(array $rows, string $pos): ?float
    {
        foreach ($rows as $row) {
            if ($row['pos'] === $pos) {
                return $row['yoy_percent'] ?? null;
            }
        }

        $this->fail("Row with pos [{$pos}] was not found.");
    }
}
