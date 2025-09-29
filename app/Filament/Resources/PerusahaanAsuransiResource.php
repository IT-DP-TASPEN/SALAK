<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PerusahaanAsuransiResource\Pages;
use App\Filament\Resources\PerusahaanAsuransiResource\RelationManagers;
use App\Models\PerusahaanAsuransi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PerusahaanAsuransiResource extends Resource
{
    protected static ?string $model = PerusahaanAsuransi::class;

    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Perusahaan Asuransi';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('asuransi_npwp')
                    ->label('NPWP')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('asuransi_nama')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('asuransi_telepon')
                    ->label('Telepon')
                    ->string()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('asuransi_alamat')
                    ->label('Alamat')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('asuransi_npwp')
                    ->label('NPWP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('asuransi_nama')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('asuransi_alamat')
                    ->label('Alamat')
                    ->searchable(),
                Tables\Columns\TextColumn::make('asuransi_telepon')
                    ->label('Telepon')
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
            'index' => Pages\ListPerusahaanAsuransis::route('/'),
            'create' => Pages\CreatePerusahaanAsuransi::route('/create'),
            'edit' => Pages\EditPerusahaanAsuransi::route('/{record}/edit'),
        ];
    }
}
