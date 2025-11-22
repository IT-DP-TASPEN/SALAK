<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchOfficeResource\Pages;
use App\Filament\Resources\BranchOfficeResource\RelationManagers;
use App\Filament\Resources\BranchOfficeResource\RelationManagers\DataAbaMasterRelationManager;
use App\Models\BranchOffice;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BranchOfficeResource extends Resource
{
    protected static ?string $model = BranchOffice::class;
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Kantor Cabang';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('branch_code')
                    ->label('Kode Cabang')
                    ->required()
                    ->maxLength(2)
                    ->columnSpanFull()
                    ->inlineLabel(),
                Forms\Components\TextInput::make('branch_code_fincloud')
                    ->label('Kode Cabang (Fincloud)')
                    ->required()
                    ->maxLength(3)
                    ->columnSpanFull()
                    ->inlineLabel(),
                Forms\Components\TextInput::make('branch_name')
                    ->label('Nama Cabang')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->inlineLabel(),
                Forms\Components\TextInput::make('branch_saldo_aba_blokir')
                    ->label('Saldo ABA Blokir')
                    ->prefix('Rp')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->columnSpanFull()
                    ->inlineLabel(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('branch_code')
                    ->label('Kode Cabang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch_code_fincloud')
                    ->label('Kode Cabang (Fincloud)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch_name')
                    ->label('Nama Cabang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch_saldo_aba_blokir')
                    ->label('Saldo ABA Blokir')
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
            ])
            ->recordUrl(fn(BranchOffice $record): ?string => static::getUrl('view', ['record' => $record]));
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
            'index' => Pages\ListBranchOffices::route('/'),
            'create' => Pages\CreateBranchOffice::route('/create'),
            'edit' => Pages\EditBranchOffice::route('/{record}/edit'),
            'view' => Pages\ViewBranchOffice::route('/{record}'),
        ];
    }
}
