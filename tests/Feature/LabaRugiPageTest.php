<?php

namespace Tests\Feature;

use App\Filament\Pages\LabaRugi;
use App\Filament\Widgets\LabaRugiFilter;
use Livewire\Livewire;
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
}
