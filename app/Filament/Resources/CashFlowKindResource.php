<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashFlowKindResource\Pages;
use App\Filament\Resources\CashFlowKindResource\RelationManagers;
use App\Models\CashFlowKind;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CashFlowKindResource extends Resource
{
    protected static ?string $model = CashFlowKind::class;
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Jenis Cash Flow';
    protected static ?string $navigationIcon = 'heroicon-o-tag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make()
                    ->columns(1)
                    ->schema([
                        Forms\Components\TextInput::make('kind_name')
                            ->label('Nama')
                            ->prefixIcon('heroicon-o-tag')
                            ->columnSpanFull()
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('kind_type')
                            ->label('Tipe')
                            ->prefixIcon('heroicon-o-arrows-right-left')
                            ->columnSpanFull()
                            ->inlineLabel()
                            ->options(function () {
                                $opts = CashFlowKind::getPossibleEnumValues('kind_type');
                                return array_combine($opts, $opts);
                            })
                            ->required(),
                        Forms\Components\Textarea::make('kind_description')
                            ->label('Deskripsi')
                            ->columnSpanFull()
                            ->inlineLabel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('kind_sort_order')
                            ->label('Urutan')
                            ->columnSpanFull()
                            ->inlineLabel()
                            ->required()
                            ->default(0)
                            ->numeric(),
                        Forms\Components\Toggle::make('kind_pusat_only')
                            ->label('Hanya untuk Kantor Pusat')
                            ->columnSpanFull()
                            ->inlineLabel()
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kind_name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('kind_type')
                    ->label('Tipe'),
                Tables\Columns\TextColumn::make('kind_description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\IconColumn::make('kind_pusat_only')
                    ->label('Kantor Pusat Saja')
                    ->boolean(),
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
                Tables\Filters\SelectFilter::make('kind_type')
                    ->options(function () {
                        $opts = CashFlowKind::getPossibleEnumValues('kind_type');
                        return array_combine($opts, $opts);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('kind_sort_order', 'asc');
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
            'index' => Pages\ListCashFlowKinds::route('/'),
            'create' => Pages\CreateCashFlowKind::route('/create'),
            'edit' => Pages\EditCashFlowKind::route('/{record}/edit'),
        ];
    }
}
