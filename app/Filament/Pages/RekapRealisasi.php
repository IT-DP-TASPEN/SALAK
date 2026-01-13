<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\RekapRealisasiStatsOverview;
use App\Filament\Widgets\RekapRealisasiDateFilter;
use App\Models\LoanOutstanding;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;

class RekapRealisasi extends Page implements HasTable
{
    use InteractsWithTable;

    public ?string $selectedDate = null;

    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $title = 'Rekap Realisasi';
    protected static string $view = 'filament.pages.rekap-realisasi';

    protected function getHeaderWidgets(): array
    {
        return [
            RekapRealisasiDateFilter::class,
            RekapRealisasiStatsOverview::class,
        ];
    }

    public function table(Table $table): Table
    {
        $asOf = $this->getAsOfDate();
        $branch = $this->getBranchCode();

        return $table
            ->paginated(false)
            ->query(
                LoanOutstanding::query()
                    ->selectRaw('loan_bi_collectability, SUM(loan_outstanding) as total_outstanding')
                    ->where('loan_date_params', $asOf)
                    ->when($branch, fn($query) => $query->where('loan_branch_office', $branch))
                    ->groupBy('loan_bi_collectability')
                    ->orderBy('loan_bi_collectability')
            )
            ->columns([
                TextColumn::make('loan_bi_collectability')
                    ->label('Kolektibilitas')
                    ->formatStateUsing(fn($state) => $this->formatKolekLabel($state)),
                TextColumn::make('total_outstanding')
                    ->label('Outstanding')
                    ->money('IDR', 0, 'id_ID')
                    ->summarize(Sum::make()),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    #[On('rekapRealisasiDateChanged')]
    public function updateSelectedDate(?string $date): void
    {
        $this->selectedDate = blank($date) ? null : Carbon::parse($date)->toDateString();
        $this->resetTable();
    }

    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->loan_bi_collectability;
    }

    private function getAsOfDate(): string
    {
        return $this->selectedDate ?? Carbon::today()->format('Y-m-d');
    }

    private function getBranchCode(): ?string
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        if (method_exists($user, 'isKantorPusatEmployee') && $user->isKantorPusatEmployee()) {
            return null;
        }

        return optional($user->branchOffice)->branch_code_fincloud;
    }

    private function formatKolekLabel($value): string
    {
        $value = is_numeric($value) ? (int) $value : null;

        return match ($value) {
            1 => '1 (L)',
            2 => '2 (DPK)',
            3 => '3 (KL)',
            4 => '4 (D)',
            5 => '5 (M)',
            default => (string) $value,
        };
    }
}
