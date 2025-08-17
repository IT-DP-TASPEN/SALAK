<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProyeksiLendingProgressResource\Pages;
use App\Filament\Resources\ProyeksiLendingProgressResource\RelationManagers;
use App\Models\ProyeksiLendingProgress;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProyeksiLendingProgressResource extends Resource
{
    protected static ?string $model = ProyeksiLendingProgress::class;
    protected static ?string $navigationGroup = 'Proyeksi Lending';
    protected static ?string $navigationLabel = 'Proyeksi Lending Status';
    protected static ?int $navigationSort = 110;
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('progress_lending')
                    ->label('Proyeksi Lending')
                    ->relationship('proyeksiLending', 'lending_nama_debitur')
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\Select::make('progress_status')
                    ->label('Status')
                    ->relationship('status', 'progress_status')
                    ->columnSpanFull()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('proyeksiLending.lending_nama_debitur')
                    ->label('Proyeksi Lending')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status.progress_status')
                    ->label('Status')
                    ->searchable()
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
            'index' => Pages\ListProyeksiLendingProgress::route('/'),
            'create' => Pages\CreateProyeksiLendingProgress::route('/create'),
            'edit' => Pages\EditProyeksiLendingProgress::route('/{record}/edit'),
        ];
    }
}
