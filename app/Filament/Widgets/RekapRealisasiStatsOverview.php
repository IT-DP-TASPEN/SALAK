<?php

namespace App\Filament\Widgets;

use App\Models\BranchOffice;
use App\Models\LoanOutstanding;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class RekapRealisasiStatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    public ?string $asOfDate = null;
    public ?string $branchCode = null;

    public function mount(): void
    {
        $user = auth()->user();
        $this->branchCode = $user && method_exists($user, 'isKantorPusatEmployee') && $user->isKantorPusatEmployee()
            ? null
            : optional($user?->branchOffice)->branch_code_fincloud;
    }

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $asOf = $this->asOfDate ?? Carbon::today()->toDateString();
        $branch = $this->branchCode;

        $kas = BranchOffice::konsolidasiSaldoKas($asOf, $branch);
        $tab = array_sum(BranchOffice::saldoNeraca2(['112'], $branch, $asOf));
        $dep = array_sum(BranchOffice::saldoNeraca2(['113'], $branch, $asOf));

        $kredit = (float) LoanOutstanding::query()
            ->where('loan_date_params', $asOf)
            ->when($branch, fn($query) => $query->where('loan_branch_office', $branch))
            ->sum('loan_outstanding');

        $aba = array_sum(BranchOffice::saldoNeraca2(['110'], $branch, $asOf));
        $asset = array_sum(BranchOffice::saldoNeraca2(['1'], $branch, $asOf));
        $npat = array_sum(BranchOffice::saldoNeraca2(['323'], $branch, $asOf));
        $npl = BranchOffice::konsolidasiNPL($asOf, $branch);
        $par = BranchOffice::konsolidasiPAR($asOf, $branch);
        $modalInti = BranchOffice::konsolidasiModalInti($asOf, $branch);
        $modalPelengkap = BranchOffice::konsolidasiModalPelengkap($asOf, $branch);
        $atmr = BranchOffice::konsolidasiATMR($asOf, $branch);
        $assetLiquid = BranchOffice::konsolidasiAssetLiquid($asOf, branch: $branch);
        $kewajibanLancar = BranchOffice::konsolidasiKewajibanLancar($asOf, $branch);

        return [
            Stat::make('Kas', $this->formatAmount($kas))
                ->icon('heroicon-o-banknotes')
                ->color('primary'),
            Stat::make('Tabungan', $this->formatAmount($tab))
                ->icon('heroicon-o-credit-card')
                ->color('primary'),
            Stat::make('Deposito', $this->formatAmount($dep))
                ->icon('heroicon-o-lock-closed')
                ->color('primary'),
            Stat::make('Asset Liquid', $this->formatAmount($assetLiquid))
                ->icon('heroicon-o-cubes')
                ->color('primary'),
            Stat::make('Kewajiban Lancar', $this->formatAmount($kewajibanLancar))
                ->icon('heroicon-o-document-duplicate')
                ->color('primary'),
            Stat::make('Kredit', $this->formatAmount($kredit))
                ->icon('heroicon-o-currency-dollar')
                ->color('primary'),
            Stat::make('ABA', $this->formatAmount($aba))
                ->icon('heroicon-o-arrow-trending-up')
                ->color('primary'),
            Stat::make('Total Asset', $this->formatAmount($asset))
                ->icon('heroicon-o-chart-bar')
                ->color('primary'),
            Stat::make('NPAT', $this->formatAmount($npat))
                ->icon('heroicon-o-presentation-chart-line')
                ->color('primary'),
            Stat::make('NPL', number_format($npl, 2, ',', '.') . '%')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($this->nplColor($npl)),
            Stat::make('PAR', number_format($par, 2, ',', '.') . '%')
                ->icon('heroicon-o-exclamation-circle')
                ->color('danger'),
            Stat::make('Modal Inti', $this->formatAmount($modalInti))
                ->icon('heroicon-o-cube-transparent')
                ->color('primary'),
            Stat::make('Modal Pelengkap', $this->formatAmount($modalPelengkap))
                ->icon('heroicon-o-cube-transparent')
                ->color('primary'),
            Stat::make('ATMR', $this->formatAmount($atmr))
                ->icon('heroicon-o-shield-check')
                ->color('primary'),
        ];
    }

    private function formatAmount(float $amount): string
    {
        return 'Rp. ' . number_format($amount, 2, ',', '.');
    }

    private function nplColor(float $npl): string
    {
        return match (true) {
            $npl <= 5 => 'success',
            $npl <= 6 => 'warning',
            default => 'danger',
        };
    }

    #[On('rekapRealisasiDateChanged')]
    public function updateSelectedDate(?string $date): void
    {
        $this->asOfDate = blank($date) ? null : Carbon::parse($date)->toDateString();
        $this->cachedStats = null;
    }
}
