<?php

namespace Tests\Feature;

use App\Filament\Pages\Neraca;
use App\Filament\Widgets\NeracaFilter;
use App\Services\NeracaReportService;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\Concerns\InteractsWithNeracaTestEnvironment;
use Tests\TestCase;

class NeracaPageTest extends TestCase
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

    public function test_head_office_user_can_filter_by_branch_and_date(): void
    {
        $this->createBranchOffice(1, '000', 'Kantor Pusat');
        $this->createBranchOffice(2, '001', 'Cabang 1');
        $this->createBranchOffice(3, '002', 'Cabang 2');

        $this->createSaldoNeraca('2026-01-08', '001', '101', 444);
        $this->createSaldoNeraca('2026-01-08', '002', '101', 888);
        $this->createSaldoNeraca('2026-01-07', '001', '101', 111);
        $this->createSaldoNeraca('2026-01-07', '002', '101', 222);
        $this->createMsoMapping('1101010000', '1.100');
        $this->createMsoBalance('2025-01-08', '001', '1.100', 222);
        $this->createMsoBalance('2025-01-08', '002', '1.100', 444);

        $user = $this->createUserWithBranch('Kantor Pusat User', 1);
        $this->grantNeracaPagePermission($user);
        $this->actingAs($user);

        Livewire::test(NeracaFilter::class)
            ->assertSee('Kantor Cabang')
            ->assertSee('Tampilkan saldo nol (0)');

        Livewire::test(Neraca::class)
            ->assertSet('selectedBranchCode', null)
            ->assertSet('selectedDate', '2026-01-08')
            ->assertSet('showZeroBalances', false)
            ->assertSee('Rp 1.332')
            ->assertSee('Saldo Tahun Lalu')
            ->assertSee('YoY%')
            ->assertSee('Rp 666')
            ->assertSee('100,00%')
            ->assertDontSee('Kas dalam Valuta Asing')
            ->call('updateSelectedBranch', '001')
            ->assertSet('selectedBranchCode', '001')
            ->assertSee('Rp 444')
            ->call('updateSelectedDate', '2026-01-07')
            ->assertSet('selectedDate', '2026-01-07')
            ->assertSee('Rp 111')
            ->call('updateSelectedBranch', null)
            ->assertSet('selectedBranchCode', null)
            ->assertSee('Rp 333');
    }

    public function test_branch_user_is_scoped_to_own_branch_and_branch_selector_is_hidden(): void
    {
        $this->createBranchOffice(1, '000', 'Kantor Pusat');
        $this->createBranchOffice(2, '001', 'Cabang 1');
        $this->createBranchOffice(3, '002', 'Cabang 2');

        $this->createSaldoNeraca('2026-01-08', '001', '101', 444);
        $this->createSaldoNeraca('2026-01-08', '002', '101', 888);

        $user = $this->createUserWithBranch('Cabang User', 2);
        $this->grantNeracaPagePermission($user);
        $this->actingAs($user);

        Livewire::test(NeracaFilter::class)
            ->assertDontSee('Kantor Cabang');

        Livewire::test(Neraca::class)
            ->assertSet('selectedBranchCode', '001')
            ->assertSee('Rp 444')
            ->call('updateSelectedBranch', '002')
            ->assertSet('selectedBranchCode', '001')
            ->assertSee('Rp 444');
    }

    public function test_show_zero_balance_checkbox_toggles_zero_rows(): void
    {
        $this->createBranchOffice(1, '000', 'Kantor Pusat');
        $this->createBranchOffice(2, '001', 'Cabang 1');

        $this->createSaldoNeraca('2026-01-08', '001', '101', 444);

        $user = $this->createUserWithBranch('Kantor Pusat User', 1);
        $this->grantNeracaPagePermission($user);
        $this->actingAs($user);

        Livewire::test(Neraca::class)
            ->assertSet('showZeroBalances', false)
            ->assertDontSee('Kas dalam Valuta Asing')
            ->call('updateShowZeroBalances', true)
            ->assertSet('showZeroBalances', true)
            ->assertSee('Kas dalam Valuta Asing')
            ->call('updateShowZeroBalances', false)
            ->assertSet('showZeroBalances', false)
            ->assertDontSee('Kas dalam Valuta Asing');
    }

    public function test_pdf_download_uses_current_user_branch_scope(): void
    {
        $this->createBranchOffice(1, '000', 'Kantor Pusat');
        $this->createBranchOffice(2, '001', 'Cabang 1');
        $this->createBranchOffice(3, '002', 'Cabang 2');

        $this->createSaldoNeraca('2026-01-08', '001', '101', 444);
        $this->createSaldoNeraca('2026-01-08', '002', '101', 888);

        $user = $this->createUserWithBranch('Cabang User', 2);
        $this->grantNeracaPagePermission($user);
        $this->actingAs($user);

        $this->mock(NeracaReportService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('build')
                ->once()
                ->with('2026-01-08', '001', false)
                ->andReturn([
                    'assets' => [
                        'label' => 'Aset',
                        'rows' => [[
                            'pos' => '1101010000',
                            'description' => 'Kas dalam Rupiah',
                            'value' => 444.0,
                            'previous_value' => 222.0,
                            'yoy_percent' => 100.0,
                            'is_total' => false,
                        ]],
                    ],
                ]);
        });

        $response = $this->get(route('neraca.pdf', [
            'date' => '2026-01-08',
            'branch' => '002',
            'show_zero_balances' => 0,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename=neraca-2026-01-08-001.pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
