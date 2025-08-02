<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProyeksiLendingResource\Pages;
use App\Filament\Resources\ProyeksiLendingResource\RelationManagers;
use App\Models\ProyeksiLending;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProyeksiLendingResource extends Resource
{
    protected static ?string $model = ProyeksiLending::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('lending_tanggal')
                    ->required(),
                // Forms\Components\Select::make('lending_kantor')
                //     ->label('Kantor Cabang')
                //     ->relationship('branchOffice', 'branch_name')
                //     ->required(),
                // Forms\Components\TextInput::make('lending_agent')
                //     ->required()
                //     ->numeric(),
                Forms\Components\TextInput::make('lending_nama_debitur')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('lending_jenis_pengajuan'),
                Forms\Components\TextInput::make('lending_produk'),
                Forms\Components\TextInput::make('lending_sumber_pembayaran'),
                Forms\Components\TextInput::make('lending_status_dapem'),
                Forms\Components\TextInput::make('lending_status_kerja'),
                Forms\Components\TextInput::make('lending_booking')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('lending_pelunasan_pokok')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('lending_booking_bersih')
                    ->numeric()
                    ->default(null),
                Forms\Components\DatePicker::make('lending_tanggal_realisasi'),
                Forms\Components\TextInput::make('lending_jkw')
                    ->numeric()
                    ->default(null),
                Forms\Components\DatePicker::make('lending_tanggal_jatuh_tempo'),
                Forms\Components\DatePicker::make('lending_tanggal_rencana_bayar'),
                Forms\Components\DatePicker::make('lending_tanggal_rencana_takeover'),
                Forms\Components\TextInput::make('lending_pot_provisi')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('lending_pot_admin')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('lending_pot_asuransi')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('lending_pot_asuransi_extra')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('lending_bunga_muka')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('lending_saldo_tab_mengendap')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('lending_angsuran_muka')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('lending_nominal_pelunasan_takeover')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('lending_booking_bersih2')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('lending_kre_rekening')
                    ->maxLength(255)
                    ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lending_tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_kantor')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_agent')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_nama_debitur')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lending_jenis_pengajuan'),
                Tables\Columns\TextColumn::make('lending_produk'),
                Tables\Columns\TextColumn::make('lending_sumber_pembayaran'),
                Tables\Columns\TextColumn::make('lending_status_dapem'),
                Tables\Columns\TextColumn::make('lending_status_kerja'),
                Tables\Columns\TextColumn::make('lending_booking')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_pelunasan_pokok')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_booking_bersih')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_tanggal_realisasi')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_jkw')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_tanggal_jatuh_tempo')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_tanggal_rencana_bayar')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_tanggal_rencana_takeover')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_pot_provisi')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_pot_admin')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_pot_asuransi')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_pot_asuransi_extra')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_bunga_muka')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_saldo_tab_mengendap')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_angsuran_muka')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_nominal_pelunasan_takeover')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_booking_bersih2')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_kre_rekening')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProyeksiLendings::route('/'),
            'create' => Pages\CreateProyeksiLending::route('/create'),
            'edit' => Pages\EditProyeksiLending::route('/{record}/edit'),
        ];
    }
}
