<?php

namespace App\Filament\Pages;

use App\Models\BranchOffice;
use App\Models\CashFlow;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class RekapCashFlow extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.rekap-cash-flow';

    public function table(Table $table): Table
    {
        // pivot from today to 1 week later
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = now()->addDays($i)->format('Y-m-d');
        }

        $columns = [];
        foreach ($dates as $date) {
            $columns[] = TextColumn::make($date)
                ->label(date('d M', strtotime($date)))
                ->getStateUsing(function (BranchOffice $record) use ($date) {
                    $totalCashIn = CashFlow::where('cash_kantor', $record->id)
                        ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                        ->whereHas('kind', fn($q) => $q->where('kind_type', 'Cash In'))
                        ->whereDate('cash_tanggal', $date)
                        ->sum('cash_jumlah');

                    $totalCashOut = CashFlow::where('cash_kantor', $record->id)
                        ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                        ->whereHas('kind', fn($q) => $q->where('kind_type', 'Cash Out'))
                        ->whereDate('cash_tanggal', $date)
                        ->sum('cash_jumlah');

                    $nett = $totalCashIn - $totalCashOut;
                    if ($nett === 0) {
                        return '<span class="text-gray-400">-</span>';
                    } else if ($nett > 0) {
                        return '<span class="text-green-600 font-semibold">↑ ' . 'Rp ' . number_format($nett, 0, ',', '.') . '</span>';
                    } else {
                        return '<span class="text-red-600 font-semibold">↓ ' . 'Rp ' . number_format($nett, 0, ',', '.') . '</span>';
                    }
                })
                ->html()
                ->alignCenter()
                ->summarize(
                    Summarizer::make()
                        ->using(function () use ($date) {
                            $totalCashIn = CashFlow::whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                                ->whereHas('kind', fn($q) => $q->where('kind_type', 'Cash In'))
                                ->whereDate('cash_tanggal', $date)
                                ->sum('cash_jumlah');

                            $totalCashOut = CashFlow::whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                                ->whereHas('kind', fn($q) => $q->where('kind_type', 'Cash Out'))
                                ->whereDate('cash_tanggal', $date)
                                ->sum('cash_jumlah');

                            $nett = $totalCashIn - $totalCashOut;
                            if ($nett === 0) {
                                return '<span class="text-gray-400">-</span>';
                            } else if ($nett > 0) {
                                return '<span class="text-green-600 font-semibold">↑ ' . 'Rp ' . number_format($nett, 0, ',', '.') . '</span>';
                            } else {
                                return '<span class="text-red-600 font-semibold">↓ ' . 'Rp ' . number_format($nett, 0, ',', '.') . '</span>';
                            }
                        })
                        ->html(),
                );
        }

        return $table
            ->query(BranchOffice::query())
            ->columns([
                TextColumn::make('branch_name')
                    ->label(''),
                ...$columns,
            ])
            ->filters([
                // ...
            ])
            ->actions([
                // ...
            ])
            ->bulkActions([
                // ...
            ]);
    }
}
