<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProyeksiLendingResource\Pages;
use App\Filament\Resources\ProyeksiLendingResource\RelationManagers;
use App\Models\MitraBayar;
use App\Models\ProdukLending;
use App\Models\ProyeksiLending;
use App\Models\ProyeksiLendingProgressStatus;
use App\Models\StatusKerja;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\DB;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

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
                        Forms\Components\DatePicker::make('lending_tanggal_lahir_debitur')
                            ->label('Tanggal Lahir Debitur')
                            ->required()
                            ->prefixIcon('heroicon-o-cake')
                            ->live()
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
                        Forms\Components\TextInput::make('lending_gaji_pokok')
                            ->label('Gaji Pokok')
                            ->required()
                            ->prefix('Rp ')
                            ->debounce()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters([',', '.'])
                            ->numeric()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_gaji_bersih')
                            ->label('Gaji Bersih')
                            ->required()
                            ->prefix('Rp ')
                            ->debounce()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters([',', '.'])
                            ->numeric()
                            ->inlineLabel(),
                        Forms\Components\Select::make('lending_status_kerja')
                            ->label('Status Kerja')
                            ->prefixIcon('heroicon-o-briefcase')
                            ->relationship(
                                'statusKerja',
                                'kerja_nama',
                            )
                            ->required()
                            ->live()
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
                    ]),
                Forms\Components\Fieldset::make('Informasi Lending')
                    ->columns(1)
                    ->schema([
                        Forms\Components\Select::make('lending_agent')
                            ->label('Agent')
                            ->prefixIcon('heroicon-o-user-group')
                            ->relationship(
                                'agent',
                                'agent_nama',
                                fn($query) => $query
                                    ->whereIn('agent_branch_office', [auth()->user()->branchOffice->id, 1])
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->inlineLabel(),
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
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                if ($get('lending_tipe_pengajuan') === 'Takeover') {
                                    // Clear takeover-related state so dependents hide
                                    $set('lending_mitra_bayar_takeover', null);
                                    $set('lending_nominal_pelunasan_takeover', null);
                                    $set('lending_tanggal_rencana_takeover', null);
                                    $set('lending_nama_koperasi_takeover', null);
                                }

                                if ($state !== 'BARU') {
                                    $set('lending_tipe_pengajuan', null);
                                }
                            })
                            ->inlineLabel(),
                        Forms\Components\Select::make('lending_tipe_pengajuan')
                            ->label('Tipe Pengajuan')
                            ->options([
                                'Takeover' => 'Takeover',
                                'Dapem sudah di Bank DP TASPEN' => 'Dapem sudah di Bank DP TASPEN',
                                'Non-takeover (Dapem di bank lain, mutasi ke Bank DP TASPEN)' => 'Non-takeover (Dapem di bank lain, mutasi ke Bank DP TASPEN)',
                            ])
                            ->prefixIcon('heroicon-o-document-text')
                            ->visible(fn($get) => $get('lending_jenis_pengajuan') === 'BARU')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state !== 'Takeover') {
                                    // Clear takeover-related state so dependents hide
                                    $set('lending_mitra_bayar_takeover', null);
                                    $set('lending_nominal_pelunasan_takeover', null);
                                    $set('lending_tanggal_rencana_takeover', null);
                                    $set('lending_nama_koperasi_takeover', null);
                                }
                            })
                            ->inlineLabel(),
                        Forms\Components\Select::make('lending_mitra_bayar_takeover')
                            ->label('Bank yang Akan di Takeover')
                            ->prefixIcon('heroicon-o-building-office-2')
                            ->relationship(
                                'mitraBayarTakeover',
                                'mitra_nama',
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->visible(fn($get) => $get('lending_tipe_pengajuan') === 'Takeover')
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                // If cleared or changed to non-KOPERASI, clear koperasi name and takeover fields as needed
                                if (!$state) {
                                    $set('lending_nominal_pelunasan_takeover', null);
                                    $set('lending_tanggal_rencana_takeover', null);
                                    $set('lending_nama_koperasi_takeover', null);
                                    return;
                                }
                                $mitra = \App\Models\MitraBayar::find($state);
                                if (!$mitra || $mitra->mitra_nama !== 'KOPERASI') {
                                    $set('lending_nama_koperasi_takeover', null);
                                }
                            })
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_nama_koperasi_takeover')
                            ->label('Nama Koperasi Takeover')
                            ->prefixIcon('heroicon-o-building-office-2')
                            ->maxLength(255)
                            ->inlineLabel()
                            ->required()
                            ->live()
                            ->visible(function (Forms\Get $get) {
                                $mitra = $get('lending_mitra_bayar_takeover');
                                return \App\Models\MitraBayar::find($mitra)?->mitra_nama === 'KOPERASI';
                            }),
                        Forms\Components\Select::make('lending_produk')
                            ->label('Produk')
                            ->prefixIcon('heroicon-o-briefcase')
                            ->relationship(
                                'produk',
                                'produk_nama',
                            )
                            ->required()
                            ->live()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_plafond')
                            ->label('Plafond')
                            ->required()
                            ->prefix('Rp ')
                            ->debounce()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters([',', '.'])
                            ->numeric()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $provisiPercent = floatval($get('lending_pot_provisi_percent'));
                                $provisi = floatval(str_replace(',', '', $get('lending_pot_provisi')));
                                $adminPercent = floatval($get('lending_pot_admin_percent'));
                                $admin = floatval(str_replace(',', '', $get('lending_pot_admin')));
                                $plafond = floatval(str_replace(',', '', $state));

                                if ($provisiPercent) {
                                    $set(
                                        'lending_pot_provisi',
                                        number_format(($plafond * $provisiPercent) / 100, 0, ',', '.')
                                    );
                                } elseif ($provisi) {
                                    $set('lending_pot_provisi_percent', $plafond ? ($provisi / $plafond) * 100 : 0);
                                }

                                if ($adminPercent) {
                                    $set(
                                        'lending_pot_admin',
                                        number_format(($plafond * $adminPercent) / 100, 0, ',', '.')
                                    );
                                } elseif ($admin) {
                                    $set('lending_pot_admin_percent', $plafond ? ($admin / $plafond) * 100 : 0);
                                }
                            })
                            ->inlineLabel(),
                        Forms\Components\Select::make('lending_asuransi_perusahaan')
                            ->label('Perusahaan Asuransi')
                            ->relationship(
                                'perusahaanAsuransi',
                                'asuransi_nama',
                            )
                            ->prefixIcon('heroicon-o-building-office')
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\Select::make('lending_with_bpjs')
                            ->label('Bundling BPJS?')
                            ->prefixIcon('heroicon-o-shield-check')
                            ->options([
                                'YA' => 'YA',
                                'TIDAK' => 'TIDAK',
                            ])
                            ->required()
                            ->live()
                            ->dehydrated(false)
                            ->visible(
                                fn($get) => $get('lending_tanggal_lahir_debitur')
                                    && Carbon::parse($get('lending_tanggal_lahir_debitur'))->diffInYears(Carbon::now()) < 65
                            )
                            ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get) {
                                if ($get('lending_bundling_bpjs')) {
                                    $set('lending_with_bpjs', 'YA');
                                } else {
                                    $set('lending_with_bpjs', 'TIDAK');
                                }
                            })
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_bunga_percent')
                            ->label('Bunga p.a. (%)')
                            ->prefix('%')
                            ->numeric()
                            ->mask(RawJs::make('$money($input)'))
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
                            ->stripCharacters([',', '.'])
                            ->debounce()
                            ->numeric()
                            ->required()
                            ->visible(fn($get) => $get('lending_jenis_pengajuan') === 'TOP UP')
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pelunasan_bunga')
                            ->label('Pelunasan Bunga')
                            ->mask(RawJs::make('$money($input)'))
                            ->prefix('Rp ')
                            ->stripCharacters([',', '.'])
                            ->debounce()
                            ->numeric()
                            ->required()
                            ->visible(fn($get) => $get('lending_jenis_pengajuan') === 'TOP UP')
                            ->inlineLabel(),
                        Forms\Components\DatePicker::make('lending_tanggal_realisasi')
                            ->label('Tanggal Realisasi')
                            ->prefixIcon('heroicon-o-calendar')
                            ->required()
                            ->live()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_jkw')
                            ->numeric()
                            ->prefixIcon('heroicon-o-clock')
                            ->label('Jangka Waktu (Bulan)')
                            ->live()
                            ->debounce()
                            ->required()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                if ($get('lending_with_bpjs') === 'YA') {
                                    // If using BPJS, calculate bundling BPJS cost
                                    $bpjs = number_format($state * 16800, 0, ',', '.');  // 16,800 per bulan
                                    $set('lending_bundling_bpjs', $bpjs);
                                } else {
                                    // Clear bundling BPJS if not using
                                    $set('lending_bundling_bpjs', null);
                                }
                            })
                            ->inlineLabel(),
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('lending_nominal_pelunasan_takeover')
                                ->label('Nominal Pelunasan Takeover')
                                ->prefix('Rp ')
                                ->mask(RawJs::make('$money($input)'))
                                ->stripCharacters([',', '.'])
                                ->numeric()
                                ->required(fn($get) => filled($get('lending_mitra_bayar_takeover')))
                                ->inlineLabel(),
                            Forms\Components\DatePicker::make('lending_tanggal_rencana_takeover')
                                ->prefixIcon('heroicon-o-calendar')
                                ->label('Tanggal Rencana Takeover')
                                ->required(fn($get) => filled($get('lending_mitra_bayar_takeover')))
                                ->inlineLabel(),
                        ])
                            ->visible(fn($get) => $get('lending_tipe_pengajuan') === 'Takeover'),
                        Forms\Components\DatePicker::make('lending_tanggal_rencana_bayar')
                            ->prefixIcon('heroicon-o-calendar')
                            ->label('Tanggal Rencana Bayar')
                            ->required()
                            ->inlineLabel(),
                    ]),
                Forms\Components\Fieldset::make('Informasi Fasilitas Aktif di BPR')
                    ->columns(1)
                    ->schema([
                        Forms\Components\Repeater::make('lending_angsuran_fasilitas_aktif')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('lending_nominal_angsuran')
                                    ->label('Nominal Angsuran')
                                    ->prefix('Rp ')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters([',', '.'])
                                    ->numeric()
                                    ->required()
                                    ->inlineLabel(),
                            ])
                            ->addActionLabel('Tambah angsuran fasilitas aktif')
                            ->defaultItems(0)
                            ->maxItems(4) // Limit to 4 active facilities, because the 5th is the new loan
                            ->reorderable(false),
                    ]),
                Forms\Components\Fieldset::make('Potongan dan Saldo')
                    ->columns(1)
                    ->schema([
                        Forms\Components\TextInput::make('lending_pot_provisi_percent')
                            ->label('Provisi (%)')
                            ->prefix('%')
                            ->required()
                            ->debounce()
                            ->live()
                            ->mask(RawJs::make('$money($input)'))
                            ->numeric()
                            ->disabled(fn($get) => blank($get('lending_plafond')))
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $plafond = floatval(str_replace(',', '', $get('lending_plafond')));
                                $state = floatval(str_replace(',', '', $state));
                                $set(
                                    'lending_pot_provisi',
                                    number_format(($plafond * $state) / 100, 0, ',', '.')
                                );
                            })
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pot_provisi')
                            ->label('Provisi')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters([',', '.'])
                            ->numeric()
                            ->required()
                            ->debounce()
                            ->live()
                            ->disabled(fn($get) => blank($get('lending_plafond')))
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $plafond = floatval(str_replace(',', '', $get('lending_plafond')));
                                $state = floatval(str_replace(',', '', $state));
                                $calculated = $plafond ? ($state / $plafond) * 100 : 0;
                                $set('lending_pot_provisi_percent', $calculated);
                            })
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pot_admin_percent')
                            ->label('Administrasi (%)')
                            ->prefix('%')
                            ->debounce()
                            ->mask(RawJs::make('$money($input)'))
                            ->disabled(fn($get) => blank($get('lending_plafond')))
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $plafond = floatval(str_replace(',', '', $get('lending_plafond')));
                                $state = floatval(str_replace(',', '', $state));
                                $set(
                                    'lending_pot_admin',
                                    number_format(($plafond * $state) / 100, 0, ',', '.')
                                );
                            })
                            ->numeric()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pot_admin')
                            ->label('Administrasi')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters([',', '.'])
                            ->debounce()
                            ->disabled(fn($get) => blank($get('lending_plafond')))
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $plafond = floatval(str_replace(',', '', $get('lending_plafond')));
                                $state = floatval(str_replace(',', '', $state));
                                $calculated = $plafond ? ($state / $plafond) * 100 : 0;
                                $set('lending_pot_admin_percent', $calculated);
                            })
                            ->numeric()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pot_premi')
                            ->label('Potongan Asuransi')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters([',', '.'])
                            ->disabled(fn($get) => blank($get('lending_plafond')))
                            ->debounce()
                            ->numeric()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $plafond = floatval(str_replace(',', '', $get('lending_plafond')));
                                $state = floatval(str_replace(',', '', $state));
                                $calculated = $plafond ? ($state / $plafond) * 100 : 0;
                                $set('lending_pot_premi_percent', $calculated);
                            })
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pot_premi_percent')
                            ->required()
                            ->label('Potongan Asuransi (%)')
                            ->prefix('%')
                            ->mask(RawJs::make('$money($input)'))
                            ->debounce()
                            ->disabled(fn($get) => blank($get('lending_plafond')))
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $plafond = floatval(str_replace(',', '', $get('lending_plafond')));
                                $state = floatval(str_replace(',', '', $state));
                                $set(
                                    'lending_pot_premi',
                                    number_format(($plafond * $state) / 100, 0, ',', '.')
                                );
                            })
                            ->numeric()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pot_premi_extra')
                            ->label('Potongan Extra Premi')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters([',', '.'])
                            ->numeric()
                            ->disabled(fn($get) => blank($get('lending_pot_premi')))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $premi = floatval(str_replace([',', '.'], '', $get('lending_pot_premi')));
                                $state = floatval(str_replace([',', '.'], '', $state));
                                $calculated = $premi ? ($state / $premi) * 100 : 0;
                                $set('lending_pot_premi_extra_percent', $calculated);
                            })
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_pot_premi_extra_percent')
                            ->required()
                            ->label('Potongan Extra Premi (%)')
                            ->prefix('%')
                            ->mask(RawJs::make('$money($input)'))
                            ->debounce()
                            ->disabled(fn($get) => blank($get('lending_pot_premi')))
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $premi = floatval(str_replace([',', '.'], '', $get('lending_pot_premi')));
                                $state = floatval(str_replace([',', '.'], '', $state));
                                $set(
                                    'lending_pot_premi_extra',
                                    number_format(($premi * $state) / 100, 0, ',', '.')
                                );
                            })
                            ->numeric()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_bundling_bpjs')
                            ->label('Bundling BPJS')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->visible(fn($get) => $get('lending_with_bpjs') === 'YA')
                            ->stripCharacters([',', '.'])
                            ->numeric()
                            ->live()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_bunga_muka')
                            ->label('Bunga Diterima di Muka')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters([',', '.'])
                            ->numeric()
                            ->required()
                            ->visible(fn($get) => ProdukLending::find($get('lending_produk'))?->produk_nama === 'DISKONTO')
                            ->live()
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
                            ->stripCharacters([',', '.'])
                            ->numeric()
                            ->required()
                            ->visibleOn('view')
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('lending_angsuran_muka')
                            ->label('Angsuran di Muka')
                            ->prefix('Rp ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters([',', '.'])
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
                Tables\Columns\TextColumn::make('approval.approval_status')
                    ->label('Status Approval')
                    ->badge()
                    ->colors([
                        'primary' => 'Pending',
                        'success' => 'Approved',
                        'danger' => 'Rejected',
                    ]),
                Tables\Columns\TextColumn::make('progress.status.progress_status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('lending_boss_application_number')
                    ->label('No. Aplikasi BOSS')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lending_tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('branchOffice.branch_name')
                    ->label('Kantor Cabang'),
                Tables\Columns\TextColumn::make('agent.agent_nama')
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
                Tables\Columns\TextColumn::make('lending_jenis_pengajuan')
                    ->label('Jenis Pengajuan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mitraBayarTakeover.mitra_nama')
                    ->label('Bank Takeover')
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
                Tables\Columns\TextColumn::make('lending_plafond')
                    ->label('Plafond')
                    ->money('IDR', 0, 'id_ID')
                    ->summarize(
                        Sum::make()
                            ->money('IDR', 0, 'id_ID')
                            ->label('Total'),
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_booking_bersih')
                    ->label('Booking Bersih')
                    ->money('IDR', 0, 'id_ID')
                    ->summarize(
                        Sum::make()
                            ->money('IDR', 0, 'id_ID')
                            ->label('Total'),
                    )
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
                DateRangeFilter::make('lending_tanggal')
                    ->label('Tanggal')
                    ->alwaysShowCalendar()
                    ->autoApply()
                    ->withIndicator()
                    ->useRangeLabels(),
                SelectFilter::make('lending_kantor')
                    ->label('Kantor Cabang')
                    ->relationship(
                        'branchOffice',
                        'branch_name',
                        fn($query) => $query->orderBy('branch_code', 'asc')
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->visible(fn() => !auth()->user()->hasRole(['bm', 'abm'])),
                SelectFilter::make('progress_lending')
                    ->label('Status Progress')
                    ->relationship(
                        'progress.status',
                        'progress_status',
                    )
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('lending_jenis_pengajuan')
                    ->label('Jenis Pengajuan')
                    ->options(
                        function () {
                            $opts = ProyeksiLending::getPossibleEnumValues('lending_jenis_pengajuan');
                            return array_combine($opts, $opts);
                        }
                    )
                    ->searchable(),
                SelectFilter::make('lending_mitra_bayar_takeover')
                    ->label('Bank Takeover')
                    ->relationship(
                        'mitraBayarTakeover',
                        'mitra_nama',
                    )
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('update_progress')
                        ->label('Update Progress')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Select::make('progress_status')
                                ->label('Status')
                                ->options(ProyeksiLendingProgressStatus::pluck('progress_status', 'id'))
                                ->required(),
                        ])
                        ->action(
                            function (ProyeksiLending $record, array $data) {
                                $record->progress()->updateOrCreate(
                                    [
                                        'progress_lending' => $record->id,
                                    ],
                                    [
                                        'progress_status' => $data['progress_status'],
                                    ]
                                );

                                // Notify the user
                                Notification::make()
                                    ->title('Status Proyeksi Lending Updated')
                                    ->body("Status proyeksi lending untuk {$record->lending_nama_debitur} telah diperbarui.")
                                    ->success()
                                    ->send();
                            }
                        )
                        ->visible(fn(ProyeksiLending $record) => (
                            auth()->user()?->can('create_proyeksi::lending::progress')
                            || auth()->user()?->can('update_proyeksi::lending::progress')
                        ) && $record->approval->approval_status === 'Approved'),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exports([
                        ExcelExport::make()
                            ->fromForm(),
                    ]),
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_update_progress')
                        ->label('Update selected progress')
                        ->icon('heroicon-o-arrow-path')
                        ->visible(function () {
                            $usr = auth()->user();
                            return $usr->can('create_proyeksi::lending::progress')
                                || $usr->can('update_proyeksi::lending::progress');
                        })
                        ->form([
                            Select::make('progress_status')
                                ->label('Status')
                                ->options(ProyeksiLendingProgressStatus::pluck('progress_status', 'id'))
                                ->required(),
                        ])
                        ->action(function ($records, $data) {
                            try {
                                DB::transaction(function () use (&$records, $data) {
                                    $approved = $records->filter(fn($record) => $record->approval->approval_status === 'Approved');

                                    foreach ($approved as $record) {
                                        $record->progress()->updateOrCreate(
                                            [
                                                'progress_lending' => $record->id,
                                            ],
                                            [
                                                'progress_status' => $data['progress_status'],
                                            ]
                                        );
                                    }

                                    $n = count($approved);
                                    $skipped = count($records) - $n;
                                    Notification::make()
                                        ->title('Status Proyeksi Lending Updated')
                                        ->body("Berhasil mengupdate {$n} proyeksi lending. {$skipped} proyeksi lending di-skip karena belum di-approve.")
                                        ->success()
                                        ->send();
                                });
                            } catch (\Exception $ex) {
                                Notification::make()
                                    ->title('Gagal Mengupdate Status Proyeksi Lending')
                                    ->body('Proyeksi lending gagal di update')
                                    ->error()
                                    ->send();
                            }
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn(ProyeksiLending $record): ?string => static::getUrl('view', ['record' => $record]));
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
                $user->hasRole(['bm', 'abm']),
                fn(Builder $query) =>
                $query->where('lending_kantor', $user->branch_office_id)
            );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Approval')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('approval.approval_status')
                            ->label('Status')
                            ->badge()
                            ->colors([
                                'primary' => 'Pending',
                                'success' => 'Approved',
                                'danger' => 'Rejected',
                            ]),
                        TextEntry::make('approval.approver.name')->label('Diperiksa Oleh')->icon('heroicon-o-user')->visible(fn($record) => filled($record->approval->approver)),
                        TextEntry::make('approval.approval_comment')->label('Catatan Approval')->icon('heroicon-o-chat-bubble-left-right')->visible(fn($record) => filled($record->approval->approval_comment)),
                    ]),

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
                        TextEntry::make('lending_kre_rekening')->label('Rekening Kredit')->icon('heroicon-o-credit-card')->visible(fn($record) => filled($record->lending_kre_rekening)),
                        TextEntry::make('sumberPembayaran.sumber_nama')->label('Sumber Pembayaran')->icon('heroicon-o-currency-dollar'),
                        TextEntry::make('statusDapem.dapem_nama')->label('Status Dapem')->icon('heroicon-o-check-badge'),
                        TextEntry::make('lending_gaji_pokok')->label('Gaji Pokok')->money('IDR', 0, 'id_ID')->icon('heroicon-o-currency-dollar'),
                        TextEntry::make('lending_gaji_bersih')->label('Gaji Bersih')->money('IDR', 0, 'id_ID')->icon('heroicon-o-currency-dollar'),
                        TextEntry::make('statusKerja.kerja_nama')->label('Status Kerja')->icon('heroicon-o-briefcase'),
                        TextEntry::make('lending_dsr')->label('DSR')->suffix('%')->numeric()->icon('heroicon-o-calculator'),
                    ]),

                Section::make('Informasi Lending')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('petugas.name')->label('Petugas Input')->icon('heroicon-o-user'),
                        TextEntry::make('lending_boss_application_number')->label('No. Aplikasi BOSS')->icon('heroicon-o-document-text')->visible(fn($record) => filled($record->lending_boss_application_number)),
                        TextEntry::make('lending_tanggal')->label('Tanggal Pengajuan')->date()->icon('heroicon-o-calendar'),
                        TextEntry::make('agent.agent_nama')->label('AO/Marketing')->icon('heroicon-o-user-group'),
                        TextEntry::make('progress.status.progress_status')->label('Progress')->icon('heroicon-o-arrow-path'),
                        TextEntry::make('lending_jenis_pengajuan')->label('Jenis Pengajuan')->icon('heroicon-o-document-text'),
                        TextEntry::make('lending_tipe_pengajuan')->label('Tipe Pengajuan')->icon('heroicon-o-document-text')->visible(fn($record) => $record->lending_jenis_pengajuan === 'BARU'),
                        TextEntry::make('mitraBayarTakeover.mitra_nama')->label('Mitra Bayar Takeover')->icon('heroicon-o-building-office-2')->visible(fn($record) => $record->lending_tipe_pengajuan === 'Takeover' && filled($record->lending_mitra_bayar_takeover)),
                        TextEntry::make('lending_nama_koperasi_takeover')->label('Nama Koperasi Takeover')->icon('heroicon-o-building-office-2')->visible(fn($record) => filled($record->lending_nama_koperasi_takeover)),
                        TextEntry::make('produk.produk_nama')->label('Produk')->icon('heroicon-o-briefcase'),
                        TextEntry::make('lending_plafond')->label('Plafond')->money('IDR', 0, 'id_ID')->icon('heroicon-o-banknotes'),
                        TextEntry::make('lending_booking_bersih')->label('Booking Bersih')->money('IDR', 0, 'id_ID')->icon('heroicon-o-banknotes'),
                        TextEntry::make('perusahaanAsuransi.asuransi_nama')->label('Perusahaan Asuransi')->icon('heroicon-o-shield-check')->visible(fn($record) => filled($record->lending_asuransi_perusahaan)),
                        TextEntry::make('bundling_bpjs')->label('Bundling BPJS?')->icon('heroicon-o-shield-check')->getStateUsing(function ($record) {
                            return isset($record->lending_bundling_bpjs) ? 'YA' : 'TIDAK';
                        }),
                        TextEntry::make('branchOffice.branch_name')->label('Kantor Cabang')->icon('heroicon-o-building-office-2'),
                        TextEntry::make('lending_bunga_percent')->label('Bunga p.a.')->suffix('%')->numeric()->icon('heroicon-o-chart-bar'),
                        TextEntry::make('lending_sistem_bunga')->label('Sistem Bunga')->icon('heroicon-o-calculator'),
                        TextEntry::make('lending_pelunasan_pokok')->label('Pelunasan Pokok')->money('IDR', 0, 'id_ID')->icon('heroicon-o-banknotes')->visible(fn($record) => $record->lending_jenis_pengajuan === 'TOP UP'),
                        TextEntry::make('lending_pelunasan_bunga')->label('Pelunasan Bunga')->money('IDR', 0, 'id_ID')->icon('heroicon-o-banknotes')->visible(fn($record) => $record->lending_jenis_pengajuan === 'TOP UP'),
                        TextEntry::make('lending_tanggal_realisasi')->label('Tanggal Realisasi')->date()->icon('heroicon-o-calendar'),
                        TextEntry::make('lending_jkw')->label('Jangka Waktu (Bulan)')->numeric()->icon('heroicon-o-clock'),
                        TextEntry::make('lending_tanggal_jatuh_tempo')->label('Tanggal Jatuh Tempo')->date()->icon('heroicon-o-banknotes'),
                        TextEntry::make('lending_nominal_pelunasan_takeover')->label('Nominal Pelunasan Takeover')->money('IDR', 0, 'id_ID')->icon('heroicon-o-banknotes')->visible(fn($record) => filled($record->lending_nominal_pelunasan_takeover)),
                        TextEntry::make('lending_tanggal_rencana_takeover')->label('Tanggal Rencana Takeover')->date()->icon('heroicon-o-calendar')->visible(fn($record) => filled($record->lending_tanggal_rencana_takeover)),
                        TextEntry::make('lending_tanggal_rencana_bayar')->label('Tanggal Rencana Bayar')->date()->icon('heroicon-o-calendar'),
                    ]),

                Section::make('Informasi Fasilitas Aktif di BPR')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('lending_angsuran_fasilitas_aktif')
                            ->label('')
                            ->schema([
                                TextEntry::make('lending_nominal_angsuran')
                                    ->label(function ($record, $state) {
                                        static $iteration = 0;
                                        $iteration++;
                                        return "Nominal Angsuran #" . $iteration;
                                    })
                                    ->money('IDR', 0, 'id_ID')
                                    ->icon('heroicon-o-banknotes'),
                            ])
                            ->contained(true)
                            ->columnSpanFull(),
                        TextEntry::make('lending_total_angsuran_fasilitas_aktif')->label('Total Angsuran Fasilitas Aktif')->money('IDR', 0, 'id_ID')->icon('heroicon-o-calculator')->getStateUsing(function ($record) {
                            if (!$record->lending_angsuran_fasilitas_aktif) {
                                return null;
                            }
                            return array_sum(array_map(fn($item) => $item['lending_nominal_angsuran'] ?? 0, $record->lending_angsuran_fasilitas_aktif));
                        })
                            ->visible(fn($record) => count($record->lending_angsuran_fasilitas_aktif) > 1),
                    ])
                    ->visible(fn($record) => filled($record->lending_angsuran_fasilitas_aktif)),

                Section::make('Potongan dan Saldo')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('lending_pot_provisi')->label('Provisi')->money('IDR', 0, 'id_ID')->icon('heroicon-o-minus-circle'),
                        TextEntry::make('lending_pot_provisi_percent')->label('Provisi (%)')->suffix('%')->numeric()->icon('heroicon-o-adjustments-horizontal'),
                        TextEntry::make('lending_pot_admin')->label('Administrasi')->money('IDR', 0, 'id_ID')->icon('heroicon-o-banknotes'),
                        TextEntry::make('lending_pot_admin_percent')->label('Administrasi (%)')->suffix('%')->numeric()->icon('heroicon-o-adjustments-horizontal'),
                        TextEntry::make('lending_pot_premi')->label('Potongan Asuransi')->money('IDR', 0, 'id_ID')->icon('heroicon-o-shield-check'),
                        TextEntry::make('lending_pot_premi_percent')->label('Potongan Asuransi (%)')->suffix('%')->numeric()->icon('heroicon-o-adjustments-horizontal'),
                        TextEntry::make('lending_pot_premi_extra')->label('Potongan Extra Premi')->money('IDR', 0, 'id_ID')->icon('heroicon-o-shield-exclamation'),
                        TextEntry::make('lending_pot_premi_extra_percent')->label('Potongan Extra Premi (%)')->suffix('%')->numeric()->icon('heroicon-o-adjustments-horizontal'),
                        TextEntry::make('lending_bundling_bpjs')->label('Bundling BPJS')->money('IDR', 0, 'id_ID')->icon('heroicon-o-banknotes')->visible(fn($record) => filled($record->lending_bundling_bpjs)),
                        TextEntry::make('lending_bunga_muka')->label('Bunga Diterima di Muka')->money('IDR', 0, 'id_ID')->icon('heroicon-o-banknotes')->visible(fn($record) => filled($record->lending_bunga_muka)),
                        TextEntry::make('lending_saldo_tab_mengendap_bulan')->label('Tabungan Mengendap (Bulan)')->suffix('bulan')->numeric()->visible(fn($record) => filled($record->lending_saldo_tab_mengendap_bulan)),
                        TextEntry::make('lending_angsuran_muka_bulan')->label('Angsuran di Muka (Bulan)')->suffix('bulan')->numeric()->visible(fn($record) => filled($record->lending_angsuran_muka_bulan)),
                        TextEntry::make('lending_saldo_tab_mengendap')->label('Tabungan Mengendap')->money('IDR', 0, 'id_ID')->icon('heroicon-o-banknotes')->visible(fn($record) => filled($record->lending_saldo_tab_mengendap)),
                        TextEntry::make('lending_angsuran_muka')->label('Angsuran di Muka')->money('IDR', 0, 'id_ID')->icon('heroicon-o-banknotes')->visible(fn($record) => filled($record->lending_angsuran_muka)),
                    ]),
            ]);
    }
}
