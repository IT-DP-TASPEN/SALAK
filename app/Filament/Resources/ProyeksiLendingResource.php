<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProyeksiLendingResource\Pages;
use App\Filament\Resources\ProyeksiLendingResource\RelationManagers;
use App\Models\ProyeksiLending;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
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
                Forms\Components\Fieldset::make('Informasi Debitur')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('lending_nama_debitur')
                            ->label('Nama Debitur')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('lending_kre_rekening')
                            ->label('Rekening Kredit')
                            ->hint('Isi ketika kredit sudah di realisasi')
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\Select::make('lending_sumber_pembayaran')
                            ->label('Sumber Pembayaran')
                            ->options(ProyeksiLending::getPossibleEnumValues('lending_sumber_pembayaran')),
                        Forms\Components\Select::make('lending_status_dapem')
                            ->label('Status Dapem')
                            ->options(ProyeksiLending::getPossibleEnumValues('lending_status_dapem')),
                        Forms\Components\Select::make('lending_status_kerja')
                            ->label('Status Kerja')
                            ->options(ProyeksiLending::getPossibleEnumValues('lending_status_kerja')),
                    ]),
                Forms\Components\Fieldset::make('Informasi Lending')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DatePicker::make('lending_tanggal')
                            ->label('Tanggal')
                            ->default(now())
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\Select::make('lending_jenis_pengajuan')
                            ->label('Jenis Pengajuan')
                            ->options(ProyeksiLending::getPossibleEnumValues('lending_jenis_pengajuan')),
                        Forms\Components\Select::make('lending_produk')
                            ->label('Produk')
                            ->options(ProyeksiLending::getPossibleEnumValues('lending_produk')),
                        Forms\Components\TextInput::make('lending_booking')
                            ->label('Booking')
                            ->required()
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric(),
                        Forms\Components\TextInput::make('lending_pelunasan_pokok')
                            ->label('Pelunasan Pokok')
                            ->mask(RawJs::make('$money($input)'))
                            ->prefix('Rp ')
                            ->stripCharacters(',')
                            ->numeric()
                            ->default(null),
                        Forms\Components\DatePicker::make('lending_tanggal_realisasi')
                            ->label('Tanggal Realisasi')
                            ->required()
                            ->reactive(),
                        Forms\Components\TextInput::make('lending_jkw')
                            ->numeric()
                            ->label('Jangka Waktu (Bulan)')
                            ->required(),
                        Forms\Components\DatePicker::make('lending_tanggal_rencana_bayar')
                            ->label('Tanggal Rencana Bayar'),
                        Forms\Components\DatePicker::make('lending_tanggal_rencana_takeover')
                            ->label('Tanggal Rencana Takeover'),
                    ]),
                Forms\Components\Fieldset::make('Potongan dan Saldo')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('lending_pot_provisi')
                            ->label('Potongan Provisi')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('lending_pot_admin')
                            ->label('Potongan Administrasi')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('lending_pot_asuransi')
                            ->label('Potongan Asuransi')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('lending_pot_asuransi_extra')
                            ->label('Potongan Asuransi Extra')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('lending_bunga_muka')
                            ->label('Bunga Diterima di Muka')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('lending_saldo_tab_mengendap')
                            ->label('Saldo Tabungan Mengendap')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('lending_angsuran_muka')
                            ->label('Angsuran di Muka')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('lending_nominal_pelunasan_takeover')
                            ->label('Nominal Pelunasan Takeover')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->default(null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lending_tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('branchOffice.branch_name')
                    ->label('Kantor Cabang'),
                Tables\Columns\TextColumn::make('agent.name')
                    ->label('AO/Marketing')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lending_nama_debitur')
                    ->label('Nama Debitur')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lending_jenis_pengajuan')
                    ->label('Jenis Pengajuan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lending_produk')
                    ->label('Produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lending_sumber_pembayaran')
                    ->label('Sumber Pembayaran')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lending_status_dapem')
                    ->label('Status Dapem')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lending_status_kerja')
                    ->label('Status Kerja')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lending_booking')
                    ->label('Booking')
                    ->money('IDR', 0, 'id_ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_pelunasan_pokok')
                    ->label('Pelunasan Pokok')
                    ->money('IDR', 0, 'id_ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_booking_bersih')
                    ->label('Booking Bersih')
                    ->money('IDR', 0, 'id_ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_tanggal_realisasi')
                    ->label('Tanggal Realisasi')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_jkw')
                    ->label('Jangka Waktu (Bulan)')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_tanggal_jatuh_tempo')
                    ->label('Tanggal Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_tanggal_rencana_bayar')
                    ->label('Tanggal Rencana Bayar')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_tanggal_rencana_takeover')
                    ->label('Tanggal Rencana Takeover')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_pot_provisi')
                    ->label('Potongan Provisi')
                    ->money('IDR', 0, 'id_ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_pot_admin')
                    ->label('Potongan Administrasi')
                    ->money('IDR', 0, 'id_ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_pot_asuransi')
                    ->label('Potongan Asuransi')
                    ->money('IDR', 0, 'id_ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_pot_asuransi_extra')
                    ->label('Potongan Asuransi Extra')
                    ->money('IDR', 0, 'id_ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_bunga_muka')
                    ->label('Bunga Diterima di Muka')
                    ->money('IDR', 0, 'id_ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_saldo_tab_mengendap')
                    ->label('Saldo Tabungan Mengendap')
                    ->money('IDR', 0, 'id_ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_angsuran_muka')
                    ->label('Angsuran di Muka')
                    ->money('IDR', 0, 'id_ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_nominal_pelunasan_takeover')
                    ->label('Nominal Pelunasan Takeover')
                    ->money('IDR', 0, 'id_ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_booking_bersih2')
                    ->label('Booking Bersih 2')
                    ->money('IDR', 0, 'id_ID')
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
