<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProdukFundingResource\Pages;
use App\Filament\Resources\ProdukFundingResource\RelationManagers;
use App\Models\ProdukFunding;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProdukFundingResource extends Resource
{
    protected static ?string $model = ProdukFunding::class;
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Produk Funding';
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('produk_jenis')
                    ->label('Jenis Produk')
                    ->options(
                        function () {
                            $opts = ProdukFunding::getPossibleEnumValues('produk_jenis');
                            return array_combine($opts, $opts);
                        }
                    )
                    ->required(),
                Forms\Components\TextInput::make('produk_nama')
                    ->label('Nama Produk')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('produk_jenis')
                    ->searchable(),
                Tables\Columns\TextColumn::make('produk_nama')
                    ->label('Nama Produk')
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
            'index' => Pages\ListProdukFundings::route('/'),
            'create' => Pages\CreateProdukFunding::route('/create'),
            'edit' => Pages\EditProdukFunding::route('/{record}/edit'),
        ];
    }
}
