<?php

namespace App\Filament\Pages;

use App\Models\BranchOffice;
use App\Models\CashFlow;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class RekapCashFlow extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.rekap-cash-flow';

    public function table(Table $table): Table
    {
        // pivot from today to 1 week later
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = now()->addDays($i)->format('Y-m-d');
        }

        $cashflows = CashFlow::query()
            ->whereBetween('cash_tanggal', [$dates[0], end($dates)])
            ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
            ->get()
            ->groupBy([
                fn($cf) => $cf->cash_kantor,  // branch_id
                fn($cf) => $cf->kind->kind_type, // Cash In / Cash Out
                fn($cf) => Carbon::parse($cf->cash_tanggal)->format('Y-m-d'), // date
            ]);

        $columns = [];
        foreach ($dates as $date) {
            $columns[] = TextColumn::make($date)
                ->label(date('d M', strtotime($date)))
                ->getStateUsing(function (BranchOffice $record) use ($date, $cashflows) {
                    if (!isset($cashflows[$record->id])) {
                        return '<span class="text-gray-400">-</span>';
                    }

                    $nett = ($cashflows->sum(fn($group) => isset($group['Cash In'][$date]) ? $group['Cash In'][$date]->sum('cash_jumlah') : 0))
                        - ($cashflows->sum(fn($group) => isset($group['Cash Out'][$date]) ? $group['Cash Out'][$date]->sum('cash_jumlah') : 0));

                    if ($nett === 0) {
                        return '<span class="text-gray-400">-</span>';
                    } elseif ($nett > 0) {
                        return '<span class="text-green-600 font-semibold">↑ ' . 'Rp ' . number_format($nett, 0, ',', '.') . '</span>';
                    } else {
                        return '<span class="text-red-600 font-semibold">↓ ' . 'Rp ' . number_format($nett, 0, ',', '.') . '</span>';
                    }
                })
                ->html()
                ->alignCenter()
                ->summarize([
                    Summarizer::make()
                        ->label('Saldo Awal')
                        ->using(function ($query) use ($date) {
                            return '<span class="font-semibold">' . 'Rp ' . number_format(static::getSaldoAwal($date), 0, ',', '.') . '</span>';
                        })
                        ->html(),
                    Summarizer::make()
                        ->label('Nett')
                        ->using(function () use ($date, $cashflows) {
                            if (!isset($cashflows)) {
                                return '<span class="text-gray-400">-</span>';
                            }

                            $nett = ($cashflows->sum(fn($group) => isset($group['Cash In'][$date]) ? $group['Cash In'][$date]->sum('cash_jumlah') : 0))
                                - ($cashflows->sum(fn($group) => isset($group['Cash Out'][$date]) ? $group['Cash Out'][$date]->sum('cash_jumlah') : 0));

                            if ($nett === 0) {
                                return '<span class="text-gray-400">-</span>';
                            } elseif ($nett > 0) {
                                return '<span class="text-green-600 font-semibold">↑ ' . 'Rp ' . number_format($nett, 0, ',', '.') . '</span>';
                            } else {
                                return '<span class="text-red-600 font-semibold">↓ ' . 'Rp ' . number_format($nett, 0, ',', '.') . '</span>';
                            }
                        })
                        ->html(),
                    Summarizer::make()
                        ->label('Saldo Akhir')
                        ->using(function ($query) use ($date, $cashflows) {
                            $nett = ($cashflows->sum(fn($group) => isset($group['Cash In'][$date]) ? $group['Cash In'][$date]->sum('cash_jumlah') : 0))
                                - ($cashflows->sum(fn($group) => isset($group['Cash Out'][$date]) ? $group['Cash Out'][$date]->sum('cash_jumlah') : 0));
                            $endingBalance = static::getSaldoAkhir($date, $nett);

                            return '<span class="font-semibold">' . 'Rp ' . number_format($endingBalance, 0, ',', '.') . '</span>';
                        })
                        ->html(),
                ]);
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

    private static function getSaldoAwal(string $date): float
    {
        $yesterday = Carbon::parse($date)->subDay()->toDateString();

        return BranchOffice::konsolidasiAssetLiquid($yesterday, true);
    }

    private static function getSaldoAkhir(string $date, float $nett): float
    {
        $endingBalance = self::getSaldoAwal($date) + $nett;

        return $endingBalance;
    }
}
