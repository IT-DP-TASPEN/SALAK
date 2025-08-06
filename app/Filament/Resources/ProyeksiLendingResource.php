<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProyeksiLendingResource\Pages;
use App\Filament\Resources\ProyeksiLendingResource\RelationManagers;
use App\Models\MitraBayar;
use App\Models\ProdukLending;
use App\Models\ProyeksiLending;
use App\Models\StatusKerja;
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
    protected static ?string $navigationGroup = 'Proyeksi Lending';
    protected static ?string $navigationLabel = 'Proyeksi Lending';
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make('Informasi Debitur')
                    ->columns(1)
                    ->schema([
                        Forms\Components\TextInput::make('lending_nama_debitur')
                            ->label('Nama Debitur')
                            ->required()
                            ->prefixIcon('heroicon-o-user')
                            ->maxLength(255)
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_notas')
                            ->label('NOTAS')
                            ->required(
                                function ($get) {
                                    $statusKerja = StatusKerja::find($get('lending_status_kerja'));
                                    if (!$statusKerja) {
                                        return false;
                                    }
                                    return in_array(
                                        $statusKerja->kerja_nama,
                                        [
                                            'PENSIUN ASN',
                                            'PENSIUN DP TASPEN',
                                            'PENSIUN ASABRI',
                                            'PRA PENSIUN ASN',
                                            'PRA PENSIUN DP TASPEN',
                                        ]
                                    );
                                }
                            )
                            ->visible(
                                function ($get) {
                                    $statusKerja = StatusKerja::find($get('lending_status_kerja'));
                                    if (!$statusKerja) {
                                        return false;
                                    }
                                    return in_array(
                                        $statusKerja->kerja_nama,
                                        [
                                            'PENSIUN ASN',
                                            'PENSIUN DP TASPEN',
                                            'PENSIUN ASABRI',
                                            'PRA PENSIUN ASN',
                                            'PRA PENSIUN DP TASPEN',
                                        ]
                                    );
                                }
                            )
                            ->prefixIcon('heroicon-o-document-text')
                            ->maxLength(255)
                            ->inlineLabel(),
                        Forms\Components\DatePicker::make('lending_tanggal_lahir_debitur')
                            ->label('Tanggal Lahir Debitur')
                            ->required()
                            ->prefixIcon('heroicon-o-cake')
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_no_hp_debitur')
                            ->label('No. HP Debitur')
                            ->required()
                            ->prefixIcon('heroicon-o-phone')
                            ->maxLength(20)
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_kre_rekening')
                            ->label('Rekening Kredit')
                            ->hint('Isi ketika kredit sudah di realisasi')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-credit-card')
                            ->default(null)
                            ->inlineLabel(),
                        Forms\Components\Select::make('lending_sumber_pembayaran')
                            ->label('Sumber Pembayaran')
                            ->prefixIcon('heroicon-o-currency-dollar')
                            ->relationship(
                                'sumberPembayaran',
                                'sumber_nama',
                            )
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\Select::make('lending_status_dapem')
                            ->label('Status Dapem')
                            ->prefixIcon('heroicon-o-check-badge')
                            ->relationship(
                                'statusDapem',
                                'dapem_nama',
                            )
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\Select::make('lending_status_kerja')
                            ->label('Status Kerja')
                            ->prefixIcon('heroicon-o-briefcase')
                            ->relationship(
                                'statusKerja',
                                'kerja_nama',
                            )
                            ->required()
                            ->reactive()
                            ->inlineLabel(),
                    ]),
                Forms\Components\Fieldset::make('Informasi Lending')
                    ->columns(1)
                    ->schema([
                        Forms\Components\Select::make('lending_mitra_bayar_takeover')
                            ->label('Mitra Bayar Takeover')
                            ->prefixIcon('heroicon-o-building-office-2')
                            ->relationship(
                                'mitraBayarTakeover',
                                'mitra_nama',
                            )
                            ->hint('Isi ketika ada takeover')
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_nama_koperasi_takeover')
                            ->label('Nama Koperasi Takeover')
                            ->prefixIcon('heroicon-o-building-office-2')
                            ->maxLength(255)
                            ->inlineLabel()
                            ->required(
                                function ($get) {
                                    $mitra = $get('lending_mitra_bayar_takeover');
                                    return MitraBayar::find($mitra)?->mitra_nama === 'KOPERASI';
                                }
                            )
                            ->visible(
                                function ($get) {
                                    $mitra = $get('lending_mitra_bayar_takeover');
                                    return MitraBayar::find($mitra)?->mitra_nama === 'KOPERASI';
                                }
                            ),
                        Forms\Components\Select::make('lending_jenis_pengajuan')
                            ->label('Jenis Pengajuan')
                            ->prefixIcon('heroicon-o-document-text')
                            ->options(
                                function () {
                                    $opts = ProyeksiLending::getPossibleEnumValues('lending_jenis_pengajuan');
                                    return array_combine($opts, $opts);
                                }
                            )
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\Select::make('lending_produk')
                            ->label('Produk')
                            ->prefixIcon('heroicon-o-briefcase')
                            ->relationship(
                                'produk',
                                'produk_nama',
                            )
                            ->required()
                            ->reactive()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_plafond')
                            ->label('Plafond')
                            ->required()
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->inlineLabel(),
                        Forms\Components\Select::make('lending_sistem_bunga')
                            ->label('Sistem Bunga')
                            ->prefixIcon('heroicon-o-calculator')
                            ->options(
                                function () {
                                    $opts = ProyeksiLending::getPossibleEnumValues('lending_sistem_bunga');
                                    return array_combine($opts, $opts);
                                }
                            )
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pelunasan_pokok')
                            ->label('Pelunasan Pokok')
                            ->mask(RawJs::make('$money($input)'))
                            ->prefix('Rp ')
                            ->stripCharacters(',')
                            ->numeric()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pelunasan_bunga')
                            ->label('Pelunasan Bunga')
                            ->mask(RawJs::make('$money($input)'))
                            ->prefix('Rp ')
                            ->stripCharacters(',')
                            ->numeric()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\DatePicker::make('lending_tanggal_realisasi')
                            ->label('Tanggal Realisasi')
                            ->prefixIcon('heroicon-o-calendar')
                            ->required()
                            ->reactive()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_jkw')
                            ->numeric()
                            ->prefixIcon('heroicon-o-clock')
                            ->label('Jangka Waktu (Bulan)')
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('lending_nominal_pelunasan_takeover')
                                ->label('Nominal Pelunasan Takeover')
                                ->prefix('Rp ')
                                ->mask(RawJs::make('$money($input)'))
                                ->stripCharacters(',')
                                ->numeric()
                                ->required(fn($get) => filled($get('lending_mitra_bayar_takeover')))
                                ->inlineLabel(),
                            Forms\Components\DatePicker::make('lending_tanggal_rencana_takeover')
                                ->prefixIcon('heroicon-o-calendar')
                                ->label('Tanggal Rencana Takeover')
                                ->required(fn($get) => filled($get('lending_mitra_bayar_takeover')))
                                ->inlineLabel(),
                        ])
                            ->visible(fn($get) => filled($get('lending_mitra_bayar_takeover'))),
                        Forms\Components\DatePicker::make('lending_tanggal_rencana_bayar')
                            ->prefixIcon('heroicon-o-calendar')
                            ->label('Tanggal Rencana Bayar')
                            ->required()
                            ->inlineLabel(),
                    ]),
                Forms\Components\Fieldset::make('Potongan dan Saldo')
                    ->columns(1)
                    ->schema([
                        Forms\Components\TextInput::make('lending_pot_provisi')
                            ->label('Potongan Provisi')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pot_admin')
                            ->label('Potongan Administrasi')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pot_premi')
                            ->label('Potongan Asuransi')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pot_premi_extra')
                            ->label('Potongan Asuransi Extra')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_bunga_muka')
                            ->label('Bunga Diterima di Muka')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->required(function ($get) {
                                $produk = $get('lending_produk');
                                return ProdukLending::find($produk)?->produk_nama === 'DISKONTO';
                            })
                            ->visible(function ($get) {
                                $produk = $get('lending_produk');
                                return ProdukLending::find($produk)?->produk_nama === 'DISKONTO';
                            })
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_saldo_tab_mengendap')
                            ->label('Saldo Tabungan Mengendap')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_angsuran_muka')
                            ->label('Angsuran di Muka')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->required()
                            ->inlineLabel(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('approvals.approval_status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'primary' => 'Pending',
                        'success' => 'Approved',
                        'danger' => 'Rejected',
                    ]),
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
                Tables\Columns\TextColumn::make('produk.produk_nama')
                    ->label('Produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sumberPembayaran.sumber_nama')
                    ->label('Sumber Pembayaran')
                    ->searchable(),
                Tables\Columns\TextColumn::make('statusDapem.dapem_nama')
                    ->label('Status Dapem')
                    ->searchable(),
                Tables\Columns\TextColumn::make('statusKerja.kerja_nama')
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
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn(ProyeksiLending $record): ?string => static::getUrl('view', ['record' => $record]))
            ->emptyStateHeading(fn(): string => 'Tidak ada data proyeksi lending yang ditemukan');
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
            'view' => Pages\ViewProyeksiLending::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $query
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->when(
                $user->hasRole(['approver', 'maker']),
                fn(Builder $query) =>
                $query->where('lending_kantor', $user->branch_office_id)
            );
    }
}
