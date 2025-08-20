<?php

namespace App\Filament\Resources\BranchOfficeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\Summarizers\Sum;

class DataAbaMasterRelationManager extends RelationManager
{
    protected static string $relationship = 'penempatanABA';
    protected static ?string $title = 'Penempatan Antar Bank';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) =>
                $query
                // ->where('aba_status', 1)
                // ->whereIn('aba_jenis', [10, 20]) // giro & tabungan umum
            )
            ->recordTitleAttribute('aba_alias')
            ->columns([
                Tables\Columns\TextColumn::make('aba_alias')
                    ->label('Bank'),
                Tables\Columns\TextColumn::make('aba_rekening')
                    ->label('Rekening'),
                Tables\Columns\TextColumn::make('aba_jenis')
                    ->label('Jenis')
                    ->formatStateUsing(fn($state) => match ($state) {
                        '10' => 'Giro',
                        '20' => 'Tabungan Umum',
                        '30' => 'Deposito Umum',
                        default => 'Lainnya',
                    }),
                Tables\Columns\TextColumn::make('aba_saldo_efektif')
                    ->label('Saldo Efektif')
                    ->summarize(
                        Sum::make()
                            ->money('IDR', 0, 'id_ID')
                            ->label('Total'),
                    )
                    ->money('IDR', 0, 'id_ID'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('aba_jenis')
                    ->label('Jenis')
                    ->multiple()
                    ->options([
                        '10' => 'Giro',
                        '20' => 'Tabungan Umum',
                        '30' => 'Deposito Umum',
                        '40' => 'Lainnya',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
