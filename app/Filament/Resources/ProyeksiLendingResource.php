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
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Infolist;

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
                            ->required()
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
                            ->required()
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
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $provisiPercent = floatval($get('lending_pot_provisi_percent'));
                                $provisi = floatval(str_replace(',', '', $get('lending_pot_provisi')));
                                $adminPercent = floatval($get('lending_pot_admin_percent'));
                                $admin = floatval(str_replace(',', '', $get('lending_pot_admin')));
                                $plafond = floatval(str_replace(',', '', $state));

                                if ($provisiPercent) {
                                    $set('lending_pot_provisi', ($plafond * $provisiPercent) / 100);
                                } elseif ($provisi) {
                                    $set('lending_pot_provisi_percent', $plafond ? ($provisi / $plafond) * 100 : 0);
                                }

                                if ($adminPercent) {
                                    $set('lending_pot_admin', ($plafond * $adminPercent) / 100);
                                } elseif ($admin) {
                                    $set('lending_pot_admin_percent', $plafond ? ($admin / $plafond) * 100 : 0);
                                }
                            })
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_bunga_percent')
                            ->label('Bunga (%)')
                            ->prefix('%')
                            ->numeric()
                            ->stripCharacters(',')
                            ->default(0)
                            ->required()
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
                            ->label('Provisi')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->required()
                            ->debounce()
                            ->reactive()
                            ->disabled(fn($get) => blank($get('lending_plafond')))
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $plafond = floatval(str_replace(',', '', $get('lending_plafond')));
                                $state = floatval(str_replace(',', '', $state));
                                $set('lending_pot_provisi_percent', ($state / $plafond) * 100);
                            })
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pot_provisi_percent')
                            ->label('Provisi (%)')
                            ->prefix('%')
                            ->required()
                            ->debounce()
                            ->reactive()
                            ->numeric()
                            ->disabled(fn($get) => blank($get('lending_plafond')))
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $plafond =  floatval(str_replace(',', '', $get('lending_plafond')));
                                $set('lending_pot_provisi', ($plafond * $state) / 100);
                            })
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pot_admin')
                            ->label('Administrasi')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->debounce()
                            ->disabled(fn($get) => blank($get('lending_plafond')))
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $plafond = floatval(str_replace(',', '', $get('lending_plafond')));
                                $state = floatval(str_replace(',', '', $state));
                                $set('lending_pot_admin_percent', ($state / $plafond) * 100);
                            })
                            ->numeric()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pot_admin_percent')
                            ->label('Administrasi (%)')
                            ->prefix('%')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->debounce()
                            ->disabled(fn($get) => blank($get('lending_plafond')))
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $plafond = floatval(str_replace(',', '', $get('lending_plafond')));
                                $set('lending_pot_admin', ($plafond * $state) / 100);
                            })
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
                            ->label('Potongan Extra Premi')
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
                            ->required()
                            ->visibleOn('view')
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_saldo_tab_mengendap_bulan')
                            ->label('Tabungan Mengendap')
                            ->postfix(' Bulan')
                            ->numeric()
                            ->required()
                            ->visibleOn('create')
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_angsuran_muka_bulan')
                            ->label('Angsuran di Muka')
                            ->postfix(' Bulan')
                            ->numeric()
                            ->required()
                            ->visibleOn('create')
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_saldo_tab_mengendap')
                            ->label('Tabungan Mengendap')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->required()
                            ->visibleOn('view')
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_angsuran_muka')
                            ->label('Angsuran di Muka')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->required()
                            ->visibleOn('view')
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
                Tables\Columns\TextColumn::make('lending_notas')
                    ->label('NOTAS')
                    ->searchable()
                    ->visible(fn($record) => in_array(
                        $record?->statusKerja?->kerja_nama,
                        [
                            'PENSIUN ASN',
                            'PENSIUN DP TASPEN',
                            'PENSIUN ASABRI',
                            'PRA PENSIUN ASN',
                            'PRA PENSIUN DP TASPEN',
                        ]
                    )),
                Tables\Columns\TextColumn::make('lending_tanggal_lahir_debitur')
                    ->label('Tanggal Lahir Debitur')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_usia_debitur')
                    ->label('Usia')
                    ->getStateUsing(function ($record) {
                        $tglLahir = $record->lending_tanggal_lahir_debitur;
                        if (!$tglLahir) {
                            return null;
                        }
                        $today = Carbon::today();
                        $tahun = (int)$today->diffInYears($tglLahir, true);
                        $bulan = (int)$today->diffInMonths($tglLahir, true) % 12;
                        $hari = (int)$today->diffInDays($tglLahir, true) % 30;
                        return "{$tahun} Tahun {$bulan} Bulan {$hari} Hari";
                    }),
                Tables\Columns\TextColumn::make('lending_no_hp_debitur')
                    ->label('No. HP Debitur')
                    ->searchable()
                    ->sortable(),
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
                Tables\Columns\TextColumn::make('mitraBayarTakeover.mitra_nama')
                    ->label('Mitra Bayar Takeover')
                    ->searchable()
                    ->visible(fn($record) => filled($record?->lending_mitra_bayar_takeover)),
                Tables\Columns\TextColumn::make('lending_nama_koperasi_takeover')
                    ->label('Nama Koperasi Takeover')
                    ->searchable(),
                // ->visible(fn($record) => dd($record)?->lending_mitra_bayar_takeover?->mitra_nama === 'KOPERASI'),
                Tables\Columns\TextColumn::make('lending_plafond')
                    ->label('Plafond')
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
                Tables\Columns\TextColumn::make('lending_pot_premi')
                    ->label('Potongan Asuransi')
                    ->money('IDR', 0, 'id_ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_pot_premi_extra')
                    ->label('Potongan Extra Premi')
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Debitur')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('lending_nama_debitur')->label('Nama Debitur')->icon('heroicon-o-user'),
                        TextEntry::make('lending_notas')->label('NOTAS')->icon('heroicon-o-document-text')->visible(fn($record) => filled($record->lending_notas)),
                        TextEntry::make('lending_tanggal_lahir_debitur')->label('Tanggal Lahir')->date()->icon('heroicon-o-cake'),
                        TextEntry::make('lending_usia_debitur')->label('Usia Debitur')->getStateUsing(function ($record) {
                            if (!$record->lending_tanggal_lahir_debitur) {
                                return null;
                            }
                            $now = \Carbon\Carbon::now();
                            $birth = \Carbon\Carbon::parse($record->lending_tanggal_lahir_debitur);
                            $years = (int)$now->diffInYears($birth, true);
                            $months = (int)$now->diffInMonths($birth, true) % 12;
                            $days = (int)$now->diffInDays($birth, true) % 30;
                            return "{$years} Tahun {$months} Bulan {$days} Hari";
                        }),
                        TextEntry::make('lending_no_hp_debitur')->label('No. HP')->icon('heroicon-o-phone'),
                        TextEntry::make('lending_kre_rekening')->label('Rekening Kredit')->icon('heroicon-o-credit-card'),
                        TextEntry::make('sumberPembayaran.sumber_nama')->label('Sumber Pembayaran')->icon('heroicon-o-currency-dollar'),
                        TextEntry::make('statusDapem.dapem_nama')->label('Status Dapem')->icon('heroicon-o-check-badge'),
                        TextEntry::make('statusKerja.kerja_nama')->label('Status Kerja')->icon('heroicon-o-briefcase'),
                    ]),

                Section::make('Informasi Lending')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('lending_tanggal')->label('Tanggal Pengajuan')->date()->icon('heroicon-o-calendar'),
                        TextEntry::make('agent.name')->label('AO/Marketing')->icon('heroicon-o-user-group'),
                        TextEntry::make('approvals.approval_status')
                            ->label('Status')
                            ->badge()
                            ->colors([
                                'primary' => 'Pending',
                                'success' => 'Approved',
                                'danger' => 'Rejected',
                            ]),
                        TextEntry::make('mitraBayarTakeover.mitra_nama')->label('Mitra Bayar Takeover')->icon('heroicon-o-building-office-2'),
                        TextEntry::make('lending_nama_koperasi_takeover')->label('Nama Koperasi Takeover')->icon('heroicon-o-building-office-2')->visible(fn($record) => filled($record->lending_nama_koperasi_takeover)),
                        TextEntry::make('lending_jenis_pengajuan')->label('Jenis Pengajuan')->icon('heroicon-o-document-text'),
                        TextEntry::make('produk.produk_nama')->label('Produk')->icon('heroicon-o-briefcase'),
                        TextEntry::make('lending_plafond')->label('Plafond')->money('IDR')->icon('heroicon-o-banknotes'),
                        TextEntry::make('lending_booking_bersih')->label('Booking Bersih')->money('IDR')->icon('heroicon-o-banknotes'),
                        TextEntry::make('lending_tanggal_jatuh_tempo')->label('Tanggal Jatuh Tempo')->date()->icon('heroicon-o-banknotes'),
                        TextEntry::make('branchOffice.branch_name')->label('Kantor Cabang')->icon('heroicon-o-building-office-2'),
                        TextEntry::make('lending_bunga_percent')->label('Bunga')->suffix('%')->numeric()->icon('heroicon-o-chart-bar'),
                        TextEntry::make('lending_sistem_bunga')->label('Sistem Bunga')->icon('heroicon-o-calculator'),
                        TextEntry::make('lending_pelunasan_pokok')->label('Pelunasan Pokok')->money('IDR')->icon('heroicon-o-banknotes'),
                        TextEntry::make('lending_pelunasan_bunga')->label('Pelunasan Bunga')->money('IDR')->icon('heroicon-o-banknotes'),
                        TextEntry::make('lending_tanggal_realisasi')->label('Tanggal Realisasi')->date()->icon('heroicon-o-calendar'),
                        TextEntry::make('lending_jkw')->label('Jangka Waktu (Bulan)')->numeric()->icon('heroicon-o-clock'),
                        TextEntry::make('lending_nominal_pelunasan_takeover')->label('Nominal Pelunasan Takeover')->money('IDR')->icon('heroicon-o-banknotes')->visible(fn($record) => filled($record->lending_nominal_pelunasan_takeover)),
                        TextEntry::make('lending_tanggal_rencana_takeover')->label('Tanggal Rencana Takeover')->date()->icon('heroicon-o-calendar')->visible(fn($record) => filled($record->lending_tanggal_rencana_takeover)),
                        TextEntry::make('lending_tanggal_rencana_bayar')->label('Tanggal Rencana Bayar')->date()->icon('heroicon-o-calendar'),
                    ]),

                Section::make('Potongan dan Saldo')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('lending_pot_provisi')->label('Provisi')->money('IDR')->icon('heroicon-o-minus-circle'),
                        TextEntry::make('lending_pot_provisi_percent')->label('Provisi (%)')->suffix('%')->numeric()->icon('heroicon-o-adjustments-horizontal'),
                        TextEntry::make('lending_pot_admin')->label('Administrasi')->money('IDR')->icon('heroicon-o-banknotes'),
                        TextEntry::make('lending_pot_admin_percent')->label('Administrasi (%)')->suffix('%')->numeric()->icon('heroicon-o-adjustments-horizontal'),
                        TextEntry::make('lending_pot_premi')->label('Potongan Asuransi')->money('IDR')->icon('heroicon-o-shield-check'),
                        TextEntry::make('lending_pot_premi_extra')->label('Potongan Extra Premi')->money('IDR')->icon('heroicon-o-shield-exclamation'),
                        TextEntry::make('lending_bunga_muka')->label('Bunga Diterima di Muka')->money('IDR')->icon('heroicon-o-cash')->visible(fn($record) => filled($record->lending_bunga_muka)),
                        TextEntry::make('lending_saldo_tab_mengendap_bulan')->label('Tabungan Mengendap (Bulan)')->suffix('bulan')->numeric()->visible(fn($record) => filled($record->lending_saldo_tab_mengendap_bulan)),
                        TextEntry::make('lending_angsuran_muka_bulan')->label('Angsuran di Muka (Bulan)')->suffix('bulan')->numeric()->visible(fn($record) => filled($record->lending_angsuran_muka_bulan)),
                        TextEntry::make('lending_saldo_tab_mengendap')->label('Tabungan Mengendap')->money('IDR')->icon('heroicon-o-banknotes')->visible(fn($record) => filled($record->lending_saldo_tab_mengendap)),
                        TextEntry::make('lending_angsuran_muka')->label('Angsuran di Muka')->money('IDR')->icon('heroicon-o-banknotes')->visible(fn($record) => filled($record->lending_angsuran_muka)),
                    ]),
            ]);
    }
}
