<?php

namespace App\Filament\Widgets;

use App\Models\BranchOffice;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class TKSRatioStatsOverview extends BaseWidget
{
    public ?string $selectedDate = null;

    protected static bool $isLazy = false;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $targetDate = $this->selectedDate ?? Carbon::today()->toDateString();

        return [
            // static::kpmm($targetDate),
            static::ckpnPerPPKA($targetDate),
            static::nplNett($targetDate),
            static::npl($targetDate),
            static::roa($targetDate),
            static::bopo($targetDate),
            static::nim($targetDate),
            static::ldr($targetDate),
            static::cashRatio($targetDate),
        ];
    }

    private static function ckpnPerPPKA(?string $tanggal = null): Stat
    {
        $ckpnPerPPKA = BranchOffice::konsolidasiCkpnPerPPKA($tanggal);

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

        return Stat::make('CKPN per PPKA', number_format($ckpnPerPPKA, 2, ',', '.') . '%')
            ->color($kshtCkpnPerPPKA['color'])
            ->description($kshtCkpnPerPPKA['description'])
            ->icon('heroicon-o-shield-exclamation');
    }

    private static function roa(?string $tanggal = null): Stat
    {
        $roa = BranchOffice::konsolidasiROA($tanggal);

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
            ->description($kshtRoa['description'])
            ->icon('heroicon-o-building-library');
    }

    private static function nim(?string $tanggal = null): Stat
    {
        $nim = BranchOffice::konsolidasiNim($tanggal);

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
            ->description($kshtNim['description'])
            ->icon('heroicon-o-percent-badge');
    }

    private static function cashRatio(?string $tanggal = null, bool $simulated = false, bool $efektif = true): Stat
    {
        $cashRatio = BranchOffice::konsolidasiCashRatio($tanggal, $simulated, $efektif);

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
            ->description($kshtCashRatio['description'])
            ->icon('heroicon-o-currency-dollar');
    }

    private static function npl(?string $tanggal = null): Stat
    {
        $nplPercentage = BranchOffice::konsolidasiNPL($tanggal);

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
            ->description('NPL ' . $kshtNpl['level'])
            ->icon('heroicon-o-exclamation-triangle');
    }

    private static function nplNett(?string $tanggal = null): Stat
    {
        $nplNettPercentage = BranchOffice::konsolidasiNPLNett($tanggal);

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
            ->description('NPL Nett ' . $kshtNplNett['level'])
            ->icon('heroicon-o-shield-check');
    }

    private static function bopo(?string $tanggal = null): Stat
    {
        $bopo = BranchOffice::konsolidasiBOPO($tanggal);

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
            ->description($kshtBopo['description'])
            ->icon('heroicon-o-calculator');
    }

    private static function ldr(?string $tanggal = null, bool $simulated = false): Stat
    {
        $nplPercentage = BranchOffice::konsolidasiNPL($tanggal);
        $ldr = BranchOffice::konsolidasiLDR($tanggal, $simulated);

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
            ->description($kshtLdr['description'])
            ->icon('heroicon-o-chart-bar');
    }

    #[On('rekapTksDateChanged')]
    public function updateSelectedDate(?string $date): void
    {
        $this->selectedDate = blank($date) ? null : Carbon::parse($date)->toDateString();
        $this->cachedStats = null;
    }
}
