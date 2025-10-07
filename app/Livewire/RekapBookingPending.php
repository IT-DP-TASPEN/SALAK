<?php

namespace App\Livewire;

use App\Models\BranchOffice;
use App\Models\ProyeksiLending;
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
            ->query(
                ProyeksiLending::query()
                    ->whereRelation('progress.status', 'progress_status', '=', 'PENDING')
                    ->orderBy('lending_tanggal', 'asc')
            )
            ->columns([
                TextColumn::make('branchOffice.branch_name')->label('Kantor Cabang'),
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
            ]);
    }

    public function render(): View
    {
        return view('livewire.rekap-booking-pending');
    }
}
