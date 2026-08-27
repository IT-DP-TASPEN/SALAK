<?php

namespace App\Filament\Widgets;

use App\Services\TksRatioSnapshotService;
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
        $snapshot = app(TksRatioSnapshotService::class)->getSnapshot($targetDate, $branch);

        $stats = [
            $this->kpmm($this->metric($snapshot, 'kpmm')),
            $this->ckpnPerPPKA($this->metric($snapshot, 'ckpn_per_ppka')),
            $this->nplNett($this->metric($snapshot, 'npl_nett')),
            $this->npl($this->metric($snapshot, 'npl')),
            $this->roa($this->metric($snapshot, 'roa')),
            $this->bopo($this->metric($snapshot, 'bopo')),
            $this->nim($this->metric($snapshot, 'nim')),
            $this->ldr($this->metric($snapshot, 'ldr'), $this->metric($snapshot, 'npl')),
            $this->cashRatio($this->metric($snapshot, 'cash_ratio')),
            $this->miapb($this->metric($snapshot, 'miapb')),
        ];

        foreach ($stats as $stat) {
            $color = $stat->getColor();
            $stat->extraAttributes([
                ...$stat->getExtraAttributes(),
                'style' => "background-image: linear-gradient(rgba(var(--{$color}-500), 0.12), rgba(var(--{$color}-500), 0.12));",
            ]);
        }

        return $stats;
    }

    /**
     * @param  array<string, float>  $snapshot
     */
    private function metric(array $snapshot, string $key): float
    {
        return (float) ($snapshot[$key] ?? 0.0);
    }

    private function miapb(float $miapb): Stat
    {
        $kshtMiapb = match (true) {
            $miapb >= 200 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
            ],
            $miapb >= 180 && $miapb < 200 => [
                'color' => 'success',
                'description' => 'Sehat',
            ],
            $miapb >= 150 && $miapb < 180 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
            ],
            $miapb >= 120 && $miapb < 150 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
            ],
            default => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
            ],
        };

        return Stat::make('MIAPB', number_format($miapb, 2, ',', '.') . '%')
            ->color($kshtMiapb['color'])
            ->description($this->descriptionWithOjk($kshtMiapb['description'], '>= 200%'))
            ->icon('heroicon-o-chart-bar');
    }

    private function descriptionWithOjk(?string $status, string $ojkRequirement): Htmlable
    {
        $statusLine = $status ? '<div>' . e($status) . '</div>' : '';

        return new HtmlString(
            $statusLine . '<div class="text-sm text-gray-500">Ketentuan OJK: ' . e($ojkRequirement) . '</div>',
        );
    }

    private function kpmm(float $kpmm): Stat
    {
        $kshtKpmm = match (true) {
            $kpmm >= 15.0 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
            ],
            $kpmm >= 13.0 && $kpmm < 15.0 => [
                'color' => 'success',
                'description' => 'Sehat',
            ],
            $kpmm >= 12.0 && $kpmm < 13.0 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
            ],
            $kpmm >= 8.0 && $kpmm < 12.0 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
            ],
            default => [ // $kpmm < 8.0
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
            ],
        };

        return Stat::make('KPMM', number_format($kpmm, 2, ',', '.') . '%')
            ->color($kshtKpmm['color'])
            ->description($this->descriptionWithOjk($kshtKpmm['description'], '>= 15%'))
            ->icon('heroicon-o-shield-check');
    }

    private function ckpnPerPPKA(float $ckpnPerPPKA): Stat
    {
        $kshtCkpnPerPPKA = match (true) {
            $ckpnPerPPKA < 50 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
            ],
            $ckpnPerPPKA >= 50 && $ckpnPerPPKA < 100 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
            ],
            default => [ // $ckpnPerPPKA >= 100
                'color' => 'danger',
                'description' => 'Tidak sehat',
            ],
        };
        (array)$kshtCkpnPerPPKA;

        return Stat::make('CKPN per PPKA', number_format($ckpnPerPPKA, 2, ',', '.') . '%')
            ->color($kshtCkpnPerPPKA['color'])
            ->description($this->descriptionWithOjk($kshtCkpnPerPPKA['description'], '< 50%'))
            ->icon('heroicon-o-shield-exclamation');
    }

    private function roa(float $roa): Stat
    {
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

    private function nim(float $nim): Stat
    {
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

    private function cashRatio(float $cashRatio): Stat
    {
        $kshtCashRatio = match (true) {
            $cashRatio >= 20.0 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
            ],
            $cashRatio >= 15.0 && $cashRatio < 20.0 => [
                'color' => 'success',
                'description' => 'Sehat',
            ],
            $cashRatio >= 10.0 && $cashRatio < 15.0 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
            ],
            $cashRatio >= 5.0 && $cashRatio < 10.0 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
            ],
            default => [ // $cashRatio < 5.0
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
            ],
        };

        return Stat::make('Cash Ratio', number_format($cashRatio, 2, ',', '.') . '%')
            ->color($kshtCashRatio['color'])
            ->description($this->descriptionWithOjk($kshtCashRatio['description'], '>= 20%'))
            ->icon('heroicon-o-currency-dollar');
    }

    private function npl(float $nplPercentage): Stat
    {
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

    private function nplNett(float $nplNettPercentage): Stat
    {
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

    private function bopo(float $bopo): Stat
    {
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

    private function ldr(float $ldr, float $nplPercentage): Stat
    {
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

        // $zone = $this->resolveLdrZone($ldr);

        return Stat::make('LDR', number_format($ldr, 2, ',', '.') . '%')
            ->color($kshtLdr['color'])
            ->description($this->descriptionWithOjk($kshtLdr['description'], '<= 90%'))
            ->icon('heroicon-o-chart-bar');
    }

    private function resolveLdrZone(float $ldr): array
    {
        return match (true) {
            $ldr >= 130 => [
                'label' => 'Stop Ekspansi',
                'range' => 'LDR ≥ 130%',
                'color' => 'danger',
                'icon' => 'heroicon-o-no-symbol',
                'description' => 'Tahan pencairan kredit baru dan fokus meningkatkan funding.',
                'actions' => [
                    'Tahan pencairan kredit baru',
                    'Fokus ke funding: bunga deposito, promo DPK, dan cross-selling',
                    'Selling asset ke bank lain sesuai RAC',
                ],
            ],

            $ldr > 90 => [
                'label' => 'Ekspansi Terbatas',
                'range' => 'LDR > 90% dan < 130%',
                'color' => 'warning',
                'icon' => 'heroicon-o-exclamation-triangle',
                'description' => 'Ekspansi kredit dilakukan secara selektif dan terukur.',
                'actions' => [
                    'Ekspansi kredit selektif dan terukur',
                    'Monitoring LDR dan likuiditas lebih intensif',
                    'Prioritaskan kredit berkualitas dan nasabah existing',
                ],
            ],

            default => [
                'label' => 'Go Ekspansi',
                'range' => 'LDR ≤ 90%',
                'color' => 'success',
                'icon' => 'heroicon-o-rocket-launch',
                'description' => 'Pencairan kredit dapat dibuka sesuai pipeline.',
                'actions' => [
                    'Pencairan kredit baru dibuka sesuai pipeline',
                    'Pastikan kenaikan DPK dan run-off pelunasan tetap terpantau',
                    'Didukung realisasi piutang asuransi',
                ],
            ],
        };
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
