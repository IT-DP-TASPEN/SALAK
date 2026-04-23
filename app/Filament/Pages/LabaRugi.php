<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LabaRugiFilter;
use App\Services\LabaRugiReportService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Pages\Page;
use Livewire\Attributes\On;

class LabaRugi extends Page
{
    use HasPageShield;

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Laba Rugi';

    protected static ?string $title = 'Laba Rugi';

    protected static string $view = 'filament.pages.laba-rugi';

    public ?string $selectedDate = null;

    public ?string $selectedBranchCode = null;

    /**
     * @var array<string, array{label: string, rows: array<int, array{pos: string, description: string, value: float, is_total: bool}>}>
     */
    public array $sections = [];

    public function mount(): void
    {
        $this->selectedDate = Carbon::today()->toDateString();
        $this->selectedBranchCode = $this->resolveInitialBranchCode();

        $this->refreshReport();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LabaRugiFilter::class,
        ];
    }

    #[On('labaRugiDateChanged')]
    public function updateSelectedDate(?string $date): void
    {
        $this->selectedDate = blank($date)
            ? Carbon::today()->toDateString()
            : Carbon::parse($date)->toDateString();

        $this->refreshReport();
    }

    #[On('labaRugiBranchOfficeChanged')]
    public function updateSelectedBranch(?string $branchCode): void
    {
        if (! $this->canSelectBranch()) {
            $this->selectedBranchCode = $this->resolveInitialBranchCode();
            $this->refreshReport();

            return;
        }

        $this->selectedBranchCode = blank($branchCode) ? null : trim($branchCode);

        $this->refreshReport();
    }

    public function formatCurrency(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    private function refreshReport(): void
    {
        $this->sections = app(LabaRugiReportService::class)->build(
            $this->selectedDate,
            $this->selectedBranchCode,
        );
    }

    private function canSelectBranch(): bool
    {
        $user = auth()->user();

        return $user && method_exists($user, 'isKantorPusatEmployee') && $user->isKantorPusatEmployee();
    }

    private function resolveInitialBranchCode(): ?string
    {
        if ($this->canSelectBranch()) {
            return null;
        }

        return optional(auth()->user()?->branchOffice)->branch_code_fincloud;
    }
}
