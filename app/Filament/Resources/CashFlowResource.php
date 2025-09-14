<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashFlowResource\Pages;
use App\Filament\Resources\CashFlowResource\RelationManagers;
use App\Models\CashFlow;
use App\Models\CashFlowKind;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                Forms\Components\Select::make('cash_kind_type')
                    ->label('Tipe')
                    ->prefixIcon('heroicon-o-arrows-right-left')
                    ->options(function () {
                        $opts = CashFlowKind::getPossibleEnumValues('kind_type');
                        return array_combine($opts, $opts);
                    })
                    ->columnSpanFull()
                    ->inlineLabel()
                    ->dehydrated()
                    ->reactive()
                    ->required(),
                Forms\Components\Select::make('cash_kind')
                    ->label('Jenis')
                    ->prefixIcon('heroicon-o-tag')
                    ->options(function (callable $get) {
                        $kind_type = $get('cash_kind_type');
                        if (!$kind_type) {
                            return [];
                        }
                        return CashFlowKind::where('kind_type', $kind_type)
                            ->when(!auth()->user()->isKantorPusatEmployee(), fn($q) => $q->where('kind_pusat_only', false))
                            ->pluck('kind_name', 'id');
                    })
                    ->disabled(fn(callable $get) => !$get('cash_kind_type'))
                    ->columnSpanFull()
                    ->inlineLabel()
                    ->required(),
                Forms\Components\DatePicker::make('cash_tanggal')
                    ->label('Tanggal')
                    ->prefixIcon('heroicon-o-calendar')
                    ->default(now())
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kind.kind_name')
                    ->label('Jenis')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('branchOffice.branch_name')
                    ->label('Kantor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cash_tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cash_keterangan')
                    ->label('Keterangan')
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
            'index' => Pages\ListCashFlows::route('/'),
            'create' => Pages\CreateCashFlow::route('/create'),
            'edit' => Pages\EditCashFlow::route('/{record}/edit'),
        ];
    }
}
