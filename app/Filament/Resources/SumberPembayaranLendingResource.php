<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SumberPembayaranLendingResource\Pages;
use App\Filament\Resources\SumberPembayaranLendingResource\RelationManagers;
use App\Models\SumberPembayaranLending;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SumberPembayaranLendingResource extends Resource
{
    protected static ?string $model = SumberPembayaranLending::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sumber_nama')
                    ->label('Sumber Pembayaran')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sumber_nama')
                    ->label('Sumber Pembayaran')
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
            'index' => Pages\ListSumberPembayaranLendings::route('/'),
            'create' => Pages\CreateSumberPembayaranLending::route('/create'),
            'edit' => Pages\EditSumberPembayaranLending::route('/{record}/edit'),
        ];
    }
}
