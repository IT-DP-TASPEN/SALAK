<?php

namespace App\Filament\Widgets;

use App\Models\BranchOffice;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

class TKSRatioStatsOverview extends BaseWidget
{
    public ?string $selectedDate = null;
    public ?string $branchCode = null;

    protected static bool $isLazy = false;

    public function mount(): void
    {
        $user = auth()->user();
        $this->branchCode = $user->isKantorPusatEmployee()
            ? null
            : optional($user->branchOffice)->branch_code_fincloud;
    }

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $targetDate = $this->selectedDate ?? Carbon::today()->toDateString();
        $branch = $this->branchCode;

        return [
            // $this->kpmm($targetDate),
            $this->ckpnPerPPKA($targetDate, $branch),
            $this->nplNett($targetDate, $branch),
            $this->npl($targetDate, $branch),
            $this->roa($targetDate, $branch),
            $this->bopo($targetDate, $branch),
            $this->nim($targetDate, $branch),
            $this->ldr($targetDate, branch: $branch),
            $this->cashRatio($targetDate, branch: $branch),
        ];
    }

    private function descriptionWithOjk(?string $status, string $ojkRequirement): Htmlable
    {
        $statusLine = $status ? '<div>' . e($status) . '</div>' : '';

        return new HtmlString(
            $statusLine . '<div class="text-sm text-gray-500">Ketentuan OJK: ' . e($ojkRequirement) . '</div>',
        );
    }

    private function ckpnPerPPKA(?string $tanggal = null, ?string $branch = null): Stat
    {
        $ckpnPerPPKA = BranchOffice::konsolidasiCkpnPerPPKA($tanggal, $branch);

        $kshtCkpnPerPPKA = match (true) {
            $ckpnPerPPKA >= 100 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
            ],
            $ckpnPerPPKA >= 80 && $ckpnPerPPKA < 100 => [
                'color' => 'success',
                'description' => 'Sehat',
            ],
            $ckpnPerPPKA >= 60 && $ckpnPerPPKA < 80 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
            ],
            $ckpnPerPPKA >= 40 && $ckpnPerPPKA < 60 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
            ],
            default => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
            ],
        };
        (array)$kshtCkpnPerPPKA;

        return Stat::make('CKPN per PPKA', number_format($ckpnPerPPKA, 2, ',', '.') . '%')
            // ->color($kshtCkpnPerPPKA['color'])
            ->description($this->descriptionWithOjk(null, '100%'))
            ->icon('heroicon-o-shield-exclamation');
    }

    private function roa(?string $tanggal = null, ?string $branch = null): Stat
    {
        $roa = BranchOffice::konsolidasiROA($tanggal, $branch);

        $kshtRoa = match (true) {
            $roa >= 2.0 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
            ],
            $roa >= 1.5 && $roa < 2 => [
                'color' => 'success',
                'description' => 'Sehat',
            ],
            $roa >= 1.0 && $roa < 1.5 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
            ],
            $roa >= 0.5 && $roa < 1 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
            ],
            default => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
            ],
        };

        return Stat::make('ROA', number_format($roa, 2, ',', '.') . '%')
            ->color($kshtRoa['color'])
            ->description($this->descriptionWithOjk($kshtRoa['description'], '>= 2%'))
            ->icon('heroicon-o-building-library');
    }

    private function nim(?string $tanggal = null, ?string $branch = null): Stat
    {
        $nim = BranchOffice::konsolidasiNim($tanggal, $branch);

        $kshtNim = match (true) {
            $nim >= 10 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
            ],
            $nim >= 8.0 && $nim < 10.0 => [
                'color' => 'success',
                'description' => 'Sehat',
            ],
            $nim >= 6.0 && $nim < 8.0 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
            ],
            $nim >= 4.0 && $nim < 6.0 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
            ],
            default => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
            ],
        };

        return Stat::make('NIM', number_format($nim, 2, ',', '.') . '%')
            ->color($kshtNim['color'])
            ->description($this->descriptionWithOjk($kshtNim['description'], '>= 10%'))
            ->icon('heroicon-o-percent-badge');
    }

    private function cashRatio(?string $tanggal = null, bool $simulated = false, bool $efektif = true, ?string $branch = null): Stat
    {
        $cashRatio = BranchOffice::konsolidasiCashRatio($tanggal, $simulated, $efektif, $branch);

        $kshtCashRatio = match (true) {
            $cashRatio >= 4.05 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
            ],
            $cashRatio >= 3.30 && $cashRatio < 4.05 => [
                'color' => 'success',
                'description' => 'Sehat',
            ],
            $cashRatio >= 2.55 && $cashRatio < 3.30 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
            ],
            $cashRatio >= 1.8 && $cashRatio < 2.55 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
            ],
            default => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
            ],
        };

        return Stat::make('Cash Ratio', number_format($cashRatio, 2, ',', '.') . '%')
            ->color($kshtCashRatio['color'])
            ->description($this->descriptionWithOjk($kshtCashRatio['description'], '>= 4.05%'))
            ->icon('heroicon-o-currency-dollar');
    }

    private function npl(?string $tanggal = null, ?string $branch = null): Stat
    {
        $nplPercentage = BranchOffice::konsolidasiNPL($tanggal, $branch);

        $kshtNpl = match (true) {
            $nplPercentage <= 5 => [
                'level' => 'Tidak signifikan',
                'color' => 'success',
            ],
            $nplPercentage > 5 && $nplPercentage <= 6 => [
                'level' => 'Kurang signifikan',
                'color' => 'warning',
            ],
            $nplPercentage > 6 && $nplPercentage <= 7 => [
                'level' => 'Cukup signifikan',
                'color' => 'danger',
            ],
            default => [
                'level' => 'Sangat signifikan',
                'color' => 'danger',
            ],
        };

        return Stat::make('NPL', number_format($nplPercentage, 2, ',', '.') . '%')
            ->color($kshtNpl['color'])
            ->description($this->descriptionWithOjk('NPL ' . $kshtNpl['level'], '<= 5%'))
            ->icon('heroicon-o-exclamation-triangle');
    }

    private function nplNett(?string $tanggal = null, ?string $branch = null): Stat
    {
        $nplNettPercentage = BranchOffice::konsolidasiNPLNett($tanggal, $branch);

        $kshtNplNett = match (true) {
            $nplNettPercentage <= 2 => [
                'level' => 'Tidak signifikan',
                'color' => 'success',
            ],
            $nplNettPercentage > 2 && $nplNettPercentage <= 3 => [
                'level' => 'Kurang signifikan',
                'color' => 'warning',
            ],
            $nplNettPercentage > 3 && $nplNettPercentage <= 4 => [
                'level' => 'Cukup signifikan',
                'color' => 'danger',
            ],
            default => [
                'level' => 'Sangat signifikan',
                'color' => 'danger',
            ],
        };

        return Stat::make('NPL Nett', number_format($nplNettPercentage, 2, ',', '.') . '%')
            ->color($kshtNplNett['color'])
            ->description($this->descriptionWithOjk('NPL Nett ' . $kshtNplNett['level'], '<= 5%'))
            ->icon('heroicon-o-shield-check');
    }

    private function bopo(?string $tanggal = null, ?string $branch = null): Stat
    {
        $bopo = BranchOffice::konsolidasiBOPO($tanggal, $branch);

        $kshtBopo = match (true) {
            $bopo <= 85 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
            ],
            $bopo > 85 && $bopo <= 90 => [
                'color' => 'success',
                'description' => 'Sehat',
            ],
            $bopo > 90 && $bopo <= 95 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
            ],
            $bopo > 95 && $bopo <= 100 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
            ],
            default => [ // bopo > 100
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
            ],
        };

        return Stat::make('BOPO', number_format($bopo, 2, ',', '.') . '%')
            ->color($kshtBopo['color'])
            ->description($this->descriptionWithOjk($kshtBopo['description'], '<= 85%'))
            ->icon('heroicon-o-calculator');
    }

    private function ldr(?string $tanggal = null, bool $simulated = false, ?string $branch = null): Stat
    {
        $nplPercentage = BranchOffice::konsolidasiNPL($tanggal, $branch);
        $ldr = BranchOffice::konsolidasiLDR($tanggal, $simulated, $branch);

        $kshtLdr = match (true) {
            $ldr <= 90 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
            ],
            $ldr > 90 && $nplPercentage <= 5 => [
                'color' => 'success',
                'description' => 'Sehat',
            ],
            $ldr > 90 && $nplPercentage > 5 && $nplPercentage <= 6 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
            ],
            $ldr > 90 && $nplPercentage > 6 && $nplPercentage <= 7 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
            ],
            $ldr > 90 && $nplPercentage > 7 => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
            ],
        };

        return Stat::make('LDR', number_format($ldr, 2, ',', '.') . '%')
            ->color($kshtLdr['color'])
            ->description($this->descriptionWithOjk($kshtLdr['description'], '<= 90%'))
            ->icon('heroicon-o-chart-bar');
    }

    #[On('rekapTksDateChanged')]
    public function updateSelectedDate(?string $date): void
    {
        $this->selectedDate = blank($date) ? null : Carbon::parse($date)->toDateString();
        $this->cachedStats = null;
    }

    #[On('rekapTksBranchOfficeChanged')]
    public function updateSelectedBranchOffice(?string $branchOfficeId): void
    {
        $user = auth()->user();
        $this->branchCode = $user->isKantorPusatEmployee()
            ? $branchOfficeId
            : optional($user->branchOffice)->branch_code_fincloud;

        $this->cachedStats = null;
    }
}
