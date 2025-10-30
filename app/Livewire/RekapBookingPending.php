<?php

namespace App\Livewire;

use App\Filament\Resources\ProyeksiLendingResource;
use App\Models\BOSSAPPFLAG;
use App\Models\BranchOffice;
use App\Models\ProyeksiLending;
use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class RekapBookingPending extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Rekap Booking Pending')
            ->paginated(false)
            ->query($this->rekapPendingQuery())
            ->columns([
                TextColumn::make('branch_name')->label('Kantor Cabang'),
                TextColumn::make('lending_nama_debitur')->label('Nama Debitur'),
                TextColumn::make('lending_plafond')->label('Plafond')->money('IDR', 0, 'id_ID'),
                TextColumn::make('lending_tanggal')->label('Tanggal Input')->date('d M Y'),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ])
            ->recordUrl(fn($record): string => ProyeksiLendingResource::getUrl('view', ['record' => $record->proyeksi_lending_id]));
    }

    public function render(): View
    {
        return view('livewire.rekap-booking-pending');
    }

    public function rekapPendingQuery(): Builder
    {
        $today = Carbon::today();

        $pendingRegNos = BOSSAPPFLAG::on('boss')
            ->whereIn('AP_CURRTRCODE', ['3.3', '7.2'])
            ->whereDate('AP_LASTTRDATE', '<=', $today)
            ->pluck('AP_REGNO')
            ->toArray();

        return BranchOffice::query()
            ->leftJoin('proyeksi_lendings', 'branch_offices.branch_code', '=', 'proyeksi_lendings.lending_kantor')
            ->whereIn('proyeksi_lendings.lending_boss_application_number', $pendingRegNos)
            ->selectRaw('
                proyeksi_lendings.id AS proyeksi_lending_id,
                branch_name,
                lending_nama_debitur,
                lending_plafond,
                lending_tanggal
            ')
            ->orderBy('branch_offices.branch_code_fincloud', 'asc');
    }

    // getTableRecordKey
    public function getTableRecordKey($record): string
    {
        return $record->branch_name . $record->lending_nama_debitur . $record->lending_plafond . $record->lending_tanggal;
    }
}
