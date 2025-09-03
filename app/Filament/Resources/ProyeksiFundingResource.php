<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProyeksiFundingResource\Pages;
use App\Filament\Resources\ProyeksiFundingResource\RelationManagers;
use App\Models\ProdukFunding;
use App\Models\ProyeksiFunding;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class ProyeksiFundingResource extends Resource
{
    protected static ?string $model = ProyeksiFunding::class;
    protected static ?string $navigationGroup = 'Proyeksi Funding';
    protected static ?string $navigationLabel = 'Proyeksi Funding';
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make()
                    ->columns(1)
                    ->schema([
                        Forms\Components\Select::make('funding_agent')
                            ->label('Agent')
                            ->prefixIcon('heroicon-o-user-group')
                            ->relationship(
                                'agent',
                                'agent_nama',
                                fn($query) => $query
                                    ->where('agent_branch_office', auth()->user()->branchOffice->id)
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\Select::make('funding_jenis')
                            ->label('Jenis Funding')
                            ->prefixIcon('heroicon-o-currency-dollar')
                            ->options(
                                function () {
                                    $opts = ProdukFunding::getPossibleEnumValues('produk_jenis');
                                    return array_combine($opts, $opts);
                                }
                            )
                            ->dehydrated(false)
                            ->reactive()
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\Select::make('funding_produk')
                            ->label('Produk Funding')
                            ->prefixIcon('heroicon-o-circle-stack')
                            ->disabled(fn(callable $get) => empty($get('funding_jenis')))
                            ->options(
                                function (callable $get) {
                                    $produkJenis = $get('funding_jenis');
                                    return ProdukFunding::where('produk_jenis', $produkJenis)
                                        ->pluck('produk_nama', 'id')
                                        ->toArray();
                                }
                            )
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\Select::make('funding_deposito_jenis')
                            ->label('Jenis Deposito')
                            ->prefixIcon('heroicon-o-currency-dollar')
                            ->visible(fn(callable $get) => $get('funding_jenis') === 'Deposito')
                            ->options(
                                function () {
                                    $opts = ProyeksiFunding::getPossibleEnumValues('funding_deposito_jenis');
                                    return array_combine($opts, $opts);
                                }
                            )
                            ->required()
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('funding_nasabah_nama')
                            ->label('Nama Nasabah')
                            ->prefixIcon('heroicon-o-user')
                            ->required()
                            ->maxLength(255)
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('funding_nominal')
                            ->label('Nominal')
                            ->mask(RawJs::make('$money($input)'))
                            ->prefix('Rp ')
                            ->stripCharacters(',')
                            ->numeric()
                            ->required()
                            ->debounce()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                $nominal = $get('funding_nominal');
                                $set('funding_nominal_bersih', $nominal);
                            })
                            ->inlineLabel(),
                        Forms\Components\TextInput::make('funding_nominal_bersih')
                            ->label('Nominal Bersih')
                            ->mask(RawJs::make('$money($input)'))
                            ->prefix('Rp ')
                            ->stripCharacters(',')
                            ->debounce()
                            ->numeric()
                            ->required()
                            ->inlineLabel(),
                    ])
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
                Tables\Columns\TextColumn::make('funding_tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('branchOffice.branch_name')
                    ->label('Kantor Cabang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('agent.agent_nama')
                    ->label('Agent')
                    ->searchable(),
                Tables\Columns\TextColumn::make('produk.produk_nama')
                    ->label('Produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('funding_deposito_jenis')
                    ->label('Jenis Penempatan Deposito')
                    ->getStateUsing(fn(ProyeksiFunding $record) => $record->funding_deposito_jenis ?? '-'),
                Tables\Columns\TextColumn::make('funding_nasabah_nama')
                    ->label('Nama Nasabah')
                    ->searchable(),
                Tables\Columns\TextColumn::make('funding_nominal')
                    ->label('Nominal')
                    ->money('IDR', 0, 'id_ID')
                    ->summarize(
                        Sum::make()
                            ->money('IDR', 0, 'id_ID')
                            ->label('Total'),
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('funding_nominal_bersih')
                    ->label('Nominal Bersih')
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
            ->recordUrl(fn(ProyeksiFunding $record): string => static::getUrl('view', ['record' => $record]));
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
            'index' => Pages\ListProyeksiFundings::route('/'),
            'create' => Pages\CreateProyeksiFunding::route('/create'),
            'edit' => Pages\EditProyeksiFunding::route('/{record}/edit'),
            'view' => Pages\ViewProyeksiFunding::route('/{record}'),
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
                Section::make('Informasi Funding')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('funding_tanggal')
                            ->label('Tanggal')
                            ->date('d M Y'),
                        TextEntry::make('branchOffice.branch_name')
                            ->label('Kantor Cabang'),
                        TextEntry::make('agent.agent_nama')
                            ->label('Agent'),
                        TextEntry::make('produk.produk_nama')
                            ->label('Produk'),
                        TextEntry::make('funding_deposito_jenis')
                            ->label('Jenis Penempatan Deposito')
                            ->getStateUsing(fn(ProyeksiFunding $record) => $record->funding_deposito_jenis ?? '-'),
                        TextEntry::make('funding_nasabah_nama')
                            ->label('Nama Nasabah'),
                        TextEntry::make('funding_nominal')
                            ->label('Nominal')
                            ->money('IDR', 0, 'id_ID'),
                        TextEntry::make('funding_nominal_bersih')
                            ->label('Nominal Bersih')
                            ->money('IDR', 0, 'id_ID'),
                    ]),
            ]);
    }
}
