<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProyeksiLendingProgressStatusResource\Pages;
use App\Filament\Resources\ProyeksiLendingProgressStatusResource\RelationManagers;
use App\Models\ProyeksiLendingProgressStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProyeksiLendingProgressStatusResource extends Resource
{
    protected static ?string $model = ProyeksiLendingProgressStatus::class;
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Status Proyeksi Lending';
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('progress_status')
                    ->label('Status')
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('progress_status')
                    ->label('Status')
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
            'index' => Pages\ListProyeksiLendingProgressStatuses::route('/'),
            'create' => Pages\CreateProyeksiLendingProgressStatus::route('/create'),
            'edit' => Pages\EditProyeksiLendingProgressStatus::route('/{record}/edit'),
        ];
    }
}
