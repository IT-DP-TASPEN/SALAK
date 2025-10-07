<?php

namespace App\Filament\Pages;

use App\Models\BranchOffice;
use App\Models\ProyeksiLending;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class RekapBooking extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.rekap-booking';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Rekap Booking Hari Ini')
            ->paginated(false)
            ->query($this->rekapBookingQuery())
            ->columns([
                TextColumn::make('branch_name')->label('Kantor Cabang'),
                TextColumn::make('booking_gross')
                    ->label('Gross')
                    ->money('IDR', 0, 'id_ID')
                    ->getStateUsing(fn($record) => $record->booking_gross ?? 0)
                    ->summarize(
                        Sum::make()
                            ->money('IDR', 0, 'id_ID')
                            ->label('Total'),
                    ),
                TextColumn::make('booking_nett')
                    ->label('Nett')
                    ->money('IDR', 0, 'id_ID')
                    ->getStateUsing(fn($record) => $record->booking_nett ?? 0)
                    ->summarize(
                        Sum::make()
                            ->money('IDR', 0, 'id_ID')
                            ->label('Total'),
                    ),
                TextColumn::make('booking_pending')
                    ->label('Pending')
                    ->money('IDR', 0, 'id_ID')
                    ->getStateUsing(fn($record) => $record->booking_pending ?? 0)
                    ->summarize(
                        Sum::make()
                            ->money('IDR', 0, 'id_ID')
                            ->label('Total'),
                    ),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }

    public function rekapBookingQuery(): Builder
    {
        $today = Carbon::today();

        $bookingScope = fn($q) => $q
            ->whereDate('lending_tanggal', $today)
            ->whereRelation('progress.status', 'progress_status', 'BOOKING');

        $pendingScope = fn($q) => $q
            ->whereDate('lending_tanggal', $today)
            ->whereRelation('progress.status', 'progress_status', 'PENDING');

        return BranchOffice::query()
            ->select('branch_offices.branch_name')
            ->withSum(['proyeksiLendings as booking_gross' => $bookingScope], 'lending_plafond')
            ->withSum(['proyeksiLendings as booking_nett'  => $bookingScope], 'lending_booking_bersih')
            ->withSum(['proyeksiLendings as booking_pending'  => $pendingScope], 'lending_plafond')
            ->orderBy('branch_offices.id');
    }

    // getTableRecordKey
    public function getTableRecordKey($record): string
    {
        return $record->branch_name . "today";
    }
}
