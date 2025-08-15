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
                        Forms\Components\Select::make('funding_jenis')
                            ->label('Jenis Funding')
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
                Tables\Columns\TextColumn::make('agent.name')
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
            'index' => Pages\ListProyeksiFundings::route('/'),
            'create' => Pages\CreateProyeksiFunding::route('/create'),
            'edit' => Pages\EditProyeksiFunding::route('/{record}/edit'),
        ];
    }
}
