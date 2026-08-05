<?php

namespace Tests\Feature;

use App\Filament\Pages\LabaRugi;
use App\Filament\Widgets\LabaRugiFilter;
use App\Services\LabaRugiReportService;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\Concerns\InteractsWithNeracaTestEnvironment;
use Tests\TestCase;

class LabaRugiPageTest extends TestCase
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

        $this->createSaldoNeraca('2026-01-08', '001', '401', -444);
        $this->createSaldoNeraca('2026-01-08', '002', '401', -888);
        $this->createSaldoNeraca('2026-01-07', '001', '401', -111);
        $this->createSaldoNeraca('2026-01-07', '002', '401', -222);
        $this->createMsoMapping('4101010201', '2.112');
        $this->createMsoBalance('2025-01-08', '001', '2.112', 222);
        $this->createMsoBalance('2025-01-08', '002', '2.112', 444);

        $user = $this->createUserWithBranch('Kantor Pusat User', 1);
        $this->grantLabaRugiPagePermission($user);
        $this->actingAs($user);

        Livewire::test(LabaRugiFilter::class)
            ->assertSee('Kantor Cabang');

        Livewire::test(LabaRugi::class)
            ->assertSet('selectedBranchCode', null)
            ->assertSet('selectedDate', '2026-01-08')
            ->assertSee('Rp 1.332')
            ->assertSee('Saldo Tahun Lalu')
            ->assertSee('YoY%')
            ->assertSee('Rp 666')
            ->assertSee('100,00%')
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

        $this->createSaldoNeraca('2026-01-08', '001', '401', -444);
        $this->createSaldoNeraca('2026-01-08', '002', '401', -888);

        $user = $this->createUserWithBranch('Cabang User', 2);
        $this->grantLabaRugiPagePermission($user);
        $this->actingAs($user);

        Livewire::test(LabaRugiFilter::class)
            ->assertDontSee('Kantor Cabang');

        Livewire::test(LabaRugi::class)
            ->assertSet('selectedBranchCode', '001')
            ->assertSee('Rp 444')
            ->call('updateSelectedBranch', '002')
            ->assertSet('selectedBranchCode', '001')
            ->assertSee('Rp 444');
    }

    public function test_pdf_action_uses_active_filters_and_opens_new_tab(): void
    {
        $this->createBranchOffice(1, '000', 'Kantor Pusat');
        $this->createBranchOffice(2, '001', 'Cabang 1');

        $user = $this->createUserWithBranch('Kantor Pusat User', 1);
        $this->grantLabaRugiPagePermission($user);
        $this->actingAs($user);

        $this->mock(LabaRugiReportService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('build')->once()->with('2026-01-08', null)->andReturn([]);
            $mock->shouldReceive('build')->once()->with('2026-01-08', '001')->andReturn([]);
            $mock->shouldReceive('build')->once()->with('2026-01-07', '001')->andReturn([]);
        });

        Livewire::test(LabaRugi::class)
            ->call('updateSelectedBranch', '001')
            ->call('updateSelectedDate', '2026-01-07')
            ->assertActionExists('downloadPdf')
            ->assertActionHasUrl('downloadPdf', route('laba-rugi.pdf', [
                'date' => '2026-01-07',
                'branch' => '001',
            ]))
            ->assertActionShouldOpenUrlInNewTab('downloadPdf');
    }

    public function test_pdf_download_uses_current_user_branch_scope(): void
    {
        $this->createBranchOffice(1, '000', 'Kantor Pusat');
        $this->createBranchOffice(2, '001', 'Cabang 1');
        $this->createBranchOffice(3, '002', 'Cabang 2');

        $user = $this->createUserWithBranch('Cabang User', 2);
        $this->grantLabaRugiPagePermission($user);
        $this->actingAs($user);

        $this->mock(LabaRugiReportService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('build')
                ->once()
                ->with('2026-01-08', '001')
                ->andReturn([
                    'operational' => [
                        'label' => 'Operasional',
                        'rows' => [[
                            'pos' => '4101010201',
                            'description' => 'Pendapatan Bunga',
                            'value' => 444.0,
                            'previous_value' => 222.0,
                            'yoy_percent' => 100.0,
                            'is_total' => false,
                        ]],
                    ],
                ]);
        });

        $response = $this->get(route('laba-rugi.pdf', [
            'date' => '2026-01-08',
            'branch' => '002',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename=laba-rugi-2026-01-08-001.pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
