<?php

namespace App\Filament\Pages;

use App\Models\BranchOffice;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class RekapPerolehan extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static string $view = 'filament.pages.rekap-perolehan';

    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->query(BranchOffice::query())
            ->columns([
                TextColumn::make('branch_name')
                    ->label('Kantor Cabang'),
                TextColumn::make('asset')
                    ->label('Asset')
                    ->money('IDR', 0, 'id_ID')
                    ->getStateUsing(fn($record) => array_sum(BranchOffice::saldoNeraca2(['1'], $record->branch_code_fincloud, $this->getAsOfDate())))
                    ->summarize(
                        Summarizer::make()
                            ->money('IDR', 0, 'id_ID')
                            ->using(fn() => array_sum(BranchOffice::saldoNeraca2(['1'], null, $this->getAsOfDate())))
                            ->label('Total'),
                    ),
                TextColumn::make('kredit')
                    ->label('Kredit')
                    ->money('IDR', 0, 'id_ID')
                    ->getStateUsing(fn($record) => array_sum(BranchOffice::saldoNeraca2(['121', '122'], $record->branch_code_fincloud, $this->getAsOfDate())))
                    ->summarize(
                        Summarizer::make()
                            ->money('IDR', 0, 'id_ID')
                            ->using(fn() => array_sum(BranchOffice::saldoNeraca2(['121', '122'], null, $this->getAsOfDate())))
                            ->label('Total'),
                    ),
                TextColumn::make('dpk_tabungan')
                    ->label('DPK Tabungan')
                    ->money('IDR', 0, 'id_ID')
                    ->getStateUsing(function ($record) {
                        $gl = BranchOffice::saldoNeraca2(['221', '2212111', '2212116'], $record->branch_code_fincloud, $this->getAsOfDate());
                        return ($gl['221'] ?? 0) - ($gl['2212111'] ?? 0) - ($gl['2212116'] ?? 0);
                    })
                    ->summarize(
                        Summarizer::make()
                            ->money('IDR', 0, 'id_ID')
                            ->using(function () {
                                $gl = BranchOffice::saldoNeraca2(['221', '2212111', '2212116'], null, $this->getAsOfDate());
                                return ($gl['221'] ?? 0) - ($gl['2212111'] ?? 0) - ($gl['2212116'] ?? 0);
                            })
                            ->label('Total'),
                    ),
                TextColumn::make('dpk_deposito')
                    ->label('DPK Deposito')
                    ->money('IDR', 0, 'id_ID')
                    ->getStateUsing(function ($record) {
                        $gl = BranchOffice::saldoNeraca2(['231', '2312202'], $record->branch_code_fincloud, $this->getAsOfDate());
                        return ($gl['231'] ?? 0) - ($gl['2312202'] ?? 0);
                    })
                    ->summarize(
                        Summarizer::make()
                            ->money('IDR', 0, 'id_ID')
                            ->using(function () {
                                $gl = BranchOffice::saldoNeraca2(['231', '2312202'], null, $this->getAsOfDate());
                                return ($gl['231'] ?? 0) - ($gl['2312202'] ?? 0);
                            })
                            ->label('Total'),
                    ),
                TextColumn::make('dpk_bank_lain')
                    ->label('DPK Simpanan Bank Lain')
                    ->money('IDR', 0, 'id_ID')
                    ->getStateUsing(function ($record) {
                        $gl = BranchOffice::saldoNeraca2(['2212111', '2212116', '2312202'], $record->branch_code_fincloud, $this->getAsOfDate());
                        return ($gl['2212111'] ?? 0) + ($gl['2212116'] ?? 0) + ($gl['2312202'] ?? 0);
                    })
                    ->summarize(
                        Summarizer::make()
                            ->money('IDR', 0, 'id_ID')
                            ->using(function () {
                                $gl = BranchOffice::saldoNeraca2(['2212111', '2212116', '2312202'], null, $this->getAsOfDate());
                                return ($gl['2212111'] ?? 0) + ($gl['2212116'] ?? 0) + ($gl['2312202'] ?? 0);
                            })
                            ->label('Total'),
                    ),
                TextColumn::make('dpk_pinjaman_yang_diterima')
                    ->label('DPK Pinjaman yang Diterima')
                    ->money('IDR', 0, 'id_ID')
                    ->getStateUsing(function ($record) {
                        $gl = BranchOffice::saldoNeraca2(['261'], $record->branch_code_fincloud, $this->getAsOfDate());
                        return $gl['261'] ?? 0;
                    })
                    ->summarize(
                        Summarizer::make()
                            ->money('IDR', 0, 'id_ID')
                            ->using(function () {
                                $gl = BranchOffice::saldoNeraca2(['261'], null, $this->getAsOfDate());
                                return $gl['261'] ?? 0;
                            })
                            ->label('Total'),
                    ),
            ])
            ->filters([
                Filter::make('as_of_date')
                    ->form([
                        DatePicker::make('date')
                            ->label('Tanggal')
                            ->default(fn() => now())
                            ->maxDate(today())
                            ->closeOnDateSelection(),
                    ])
                    ->query(function ($query, array $data) {
                        // The table query itself stays the same; we use the filter state in column callbacks.
                        return $query;
                    })
                    ->label('Per Tanggal'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exports([
                        ExcelExport::make('rekap')
                            ->fromTable()
                            ->ignoreFormatting()
                            ->withFilename(fn() => 'Rekap Perolehan ' . ($this->getAsOfDate() ?? now()->toDateString())),
                    ]),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }

    private function getAsOfDate(): ?string
    {
        $date = $this->getTableFilterState('as_of_date')['date'] ?? null;

        return blank($date) ? null : $date;
    }
}
