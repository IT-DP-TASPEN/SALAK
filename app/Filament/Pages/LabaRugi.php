<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LabaRugiFilter;
use App\Services\LabaRugiReportService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Actions\Action;
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
     * @var array<string, array{label: string, rows: array<int, array<string, mixed>>}>
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('laba-rugi.pdf', [
                    'date' => $this->selectedDate,
                    'branch' => $this->selectedBranchCode,
                ]))
                ->openUrlInNewTab(),
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

    public function formatPercentage(?float $value): string
    {
        if ($value === null) {
            return '-';
        }

        return number_format($value, 2, ',', '.').'%';
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
