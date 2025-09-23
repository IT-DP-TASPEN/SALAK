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

class RekapCashFlow extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.rekap-cash-flow';

    public function table(Table $table): Table
    {
        // tanggal pivot dari hari ini + 7 hari
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = now()->addDays($i)->format('Y-m-d');
        }

        // Ambil cashflow ter-approve
        $cashflows = CashFlow::query()
            ->whereBetween('cash_tanggal', [$dates[0], end($dates)])
            ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
            ->get()
            ->groupBy(fn($cf) => Carbon::parse($cf->cash_tanggal)->format('Y-m-d'));

        // Hitung chain saldo
        $balances = [];
        $runningSaldo = $this->getInitialSaldo($dates[0]); // saldo awal sebelum periode

        foreach ($dates as $date) {
            $nett = 0;
            if (isset($cashflows[$date])) {
                $nett = $cashflows[$date]
                    ->where(fn($cf) => $cf->kind->kind_type === 'Cash In')
                    ->sum('cash_jumlah')
                    - $cashflows[$date]
                    ->where(fn($cf) => $cf->kind->kind_type === 'Cash Out')
                    ->sum('cash_jumlah');
            }

            $awal = $runningSaldo;
            $akhir = $awal + $nett;

            $balances[$date] = [
                'awal' => $awal,
                'nett' => $nett,
                'akhir' => $akhir,
            ];

            $runningSaldo = $akhir;
        }

        // Buat kolom per tanggal
        $columns = [];
        foreach ($dates as $date) {
            $columns[] = TextColumn::make($date)
                ->label(date('d M', strtotime($date)))
                ->getStateUsing(function (BranchOffice $record) use ($date) {
                    $nett = $record->cashFlows()
                        ->where('cash_tanggal', $date)
                        ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                        ->whereHas('kind', fn($q) => $q->where('kind_type', 'Cash In'))
                        ->sum('cash_jumlah')
                        - $record->cashFlows()
                        ->where('cash_tanggal', $date)
                        ->whereHas('approval', fn($q) => $q->where('approval_status', 'Approved'))
                        ->whereHas('kind', fn($q) => $q->where('kind_type', 'Cash Out'))
                        ->sum('cash_jumlah');

                    return $this->formatNett($nett);
                })
                ->html()
                ->alignCenter()
                ->summarize([
                    Summarizer::make()
                        ->label('Saldo Awal')
                        ->using(fn() => $this->formatMoney($balances[$date]['awal']))
                        ->html(),
                    Summarizer::make()
                        ->label('Nett')
                        ->using(fn() => $this->formatNett($balances[$date]['nett']))
                        ->html(),
                    Summarizer::make()
                        ->label('Saldo Akhir')
                        ->using(fn() => $this->formatMoney($balances[$date]['akhir']))
                        ->html(),
                    Summarizer::make()
                        ->label('Cash Ratio')
                        ->using(fn() => number_format(BranchOffice::konsolidasiCashRatio2($date, $balances[$date]['akhir']), 2, ',', '.') . '%')
                        ->html(),
                ]);
        }

        return $table
            ->query(BranchOffice::query())
            ->columns([
                TextColumn::make('branch_name')->label(''),
                ...$columns,
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    private function getInitialSaldo(string $firstDate): float
    {
        $yesterday = Carbon::parse($firstDate)->subDay()->toDateString();
        return BranchOffice::konsolidasiAssetLiquid($yesterday, true);
    }

    private function formatMoney(float $value): string
    {
        return '<span class="font-semibold">Rp ' . number_format($value, 0, ',', '.') . '</span>';
    }

    private function formatNett(float $nett): string
    {
        if ($nett === 0.0) {
            return '<span class="text-gray-400">-</span>';
        } elseif ($nett > 0) {
            return '<span class="text-green-600 font-semibold">↑ Rp ' . number_format($nett, 0, ',', '.') . '</span>';
        } else {
            return '<span class="text-red-600 font-semibold">↓ Rp ' . number_format($nett, 0, ',', '.') . '</span>';
        }
    }
}
