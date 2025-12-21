<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashFlowResource\Pages;
use App\Filament\Resources\CashFlowResource\RelationManagers;
use App\Models\CashFlow;
use App\Models\CashFlowKind;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class CashFlowResource extends Resource
{
    protected static ?string $model = CashFlow::class;
    protected static ?string $navigationGroup = 'Cash Flow';
    protected static ?string $navigationLabel = 'Cash Flow';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make()
                    ->columns(1)
                    ->schema([
                        Forms\Components\Select::make('cash_kind_type')
                            ->label('Tipe')
                            ->prefixIcon('heroicon-o-arrows-right-left')
                            ->options(function () {
                                $opts = CashFlowKind::getPossibleEnumValues('kind_type');
                                return array_combine($opts, $opts);
                            })
                            ->columnSpanFull()
                            ->inlineLabel()
                            ->reactive()
                            ->dehydrated(false) // helper field; do not save to DB
                            ->required(),
                        Forms\Components\Select::make('cash_kind')
                            ->label('Jenis')
                            ->prefixIcon('heroicon-o-tag')
                            ->options(function (callable $get): array {
                                $type = $get('cash_kind_type');

                                // When editing, infer type from selected cash_kind if not set yet
                                if (!$type) {
                                    $selectedId = $get('cash_kind');
                                    if ($selectedId) {
                                        $type = CashFlowKind::whereKey($selectedId)->value('kind_type');
                                    }
                                }

                                if (!$type) {
                                    return [];
                                }

                                return CashFlowKind::query()
                                    ->where('kind_type', $type)
                                    ->when(!auth()->user()->isKantorPusatEmployee(), fn($q) => $q->where('kind_pusat_only', false))
                                    ->orderBy('kind_sort_order', 'asc')
                                    ->pluck('kind_name', 'id')
                                    ->all();
                            })
                            ->afterStateHydrated(function (callable $set, $state): void {
                                // Ensure the dependent type is initialized on edit
                                if ($state) {
                                    $type = CashFlowKind::whereKey($state)->value('kind_type');
                                    if ($type) {
                                        $set('cash_kind_type', $type);
                                    }
                                }
                            })
                            ->disabled(fn(callable $get) => !$get('cash_kind_type'))
                            ->searchable()
                            ->preload()
                            ->columnSpanFull()
                            ->inlineLabel()
                            ->reactive()
                            ->required(),
                        Forms\Components\DatePicker::make('cash_tanggal')
                            ->label('Tanggal')
                            ->prefixIcon('heroicon-o-calendar')
                            ->default(now())
                            ->minDate(Carbon::today())
                            ->columnSpanFull()
                            ->inlineLabel()
                            ->required(),
                        Forms\Components\Textarea::make('cash_keterangan')
                            ->label('Keterangan')
                            ->columnSpanFull()
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('cash_jumlah')
                            ->label('Jumlah')
                            ->required()
                            ->prefix('Rp ')
                            ->debounce()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters([',', '.'])
                            ->numeric()
                            ->columnSpanFull()
                            ->inlineLabel(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('approval.approval_status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'primary' => 'Pending',
                        'success' => 'Approved',
                        'danger' => 'Rejected',
                    ]),
                Tables\Columns\TextColumn::make('kind.kind_type')
                    ->label('Tipe')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('kind.kind_name')
                    ->label('Jenis')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('branchOffice.branch_name')
                    ->label('Kantor Cabang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Petugas Input')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cash_tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cash_keterangan')
                    ->label('Keterangan')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('cash_jumlah')
                    ->label('Jumlah')
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
                DateRangeFilter::make('cash_tanggal')
                    ->label('Tanggal')
                    ->alwaysShowCalendar()
                    ->autoApply()
                    ->withIndicator()
                    ->useRangeLabels(),
                SelectFilter::make('cash_kantor')
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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('cash_tanggal', 'desc')
            ->recordUrl(fn(CashFlow $record) => static::getUrl('view', ['record' => $record]));
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
            'index' => Pages\ListCashFlows::route('/'),
            'create' => Pages\CreateCashFlow::route('/create'),
            'edit' => Pages\EditCashFlow::route('/{record}/edit'),
            'view' => Pages\ViewCashFlow::route('/{record}'),
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
                $query->where('cash_kantor', $user->branch_office_id)
            );
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->whereHas('approval', function (Builder $query) {
                $query->where('approval_status', 'Pending');
            })
            ->count();

        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pending Proyeksi Cash Flow';
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
                            ->label('Status Approval')
                            ->badge()
                            ->colors([
                                'primary' => 'Pending',
                                'success' => 'Approved',
                                'danger' => 'Rejected',
                            ]),
                        TextEntry::make('approval.approver.name')->label('Diperiksa Oleh')->icon('heroicon-o-user')->visible(fn($record) => filled($record->approval->approver)),
                        TextEntry::make('approval.approval_comment')->label('Catatan Approval')->icon('heroicon-o-chat-bubble-left-right')->visible(fn($record) => filled($record->approval->approval_comment)),
                    ]),

                Section::make('Detail')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('kind.kind_type')
                            ->label('Tipe'),
                        TextEntry::make('kind.kind_name')
                            ->label('Jenis'),
                        TextEntry::make('branchOffice.branch_name')
                            ->label('Kantor'),
                        TextEntry::make('user.name')
                            ->label('Petugas Input'),
                        TextEntry::make('cash_tanggal')
                            ->label('Tanggal')
                            ->date('d M Y'),
                        TextEntry::make('cash_keterangan')
                            ->label('Keterangan'),
                        TextEntry::make('cash_jumlah')
                            ->label('Jumlah')
                            ->money('IDR', 0, 'id_ID'),
                    ])
                    ->columns(2),
            ]);
    }
}
