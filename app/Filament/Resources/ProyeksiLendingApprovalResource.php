<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProyeksiLendingApprovalResource\Pages;
use App\Filament\Resources\ProyeksiLendingApprovalResource\RelationManagers;
use App\Models\ProyeksiLendingApproval;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProyeksiLendingApprovalResource extends Resource
{
    protected static ?string $model = ProyeksiLendingApproval::class;
    protected static ?string $navigationGroup = 'Proyeksi Lending';
    protected static ?string $navigationLabel = 'Approval Proyeksi Lending';
    protected static ?int $navigationSort = 100;
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('approval_lending')
                    ->label('Lending')
                    ->relationship('proyeksiLending', 'lending_nama_debitur')
                    ->required(),
                Forms\Components\Select::make('approval_status')
                    ->label('Status')
                    ->options(function () {
                        $options = ProyeksiLendingApproval::getPossibleEnumValues('approval_status');
                        return array_combine($options, $options);
                    })
                    ->required(),
                Forms\Components\Textarea::make('approval_comment')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('proyeksiLending.lending_nama_debitur')
                    ->label('Lending')
                    ->searchable(),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Penyetuju')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approval_status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'primary' => 'Pending',
                        'success' => 'Approved',
                        'danger' => 'Rejected',
                    ]),
                Tables\Columns\TextColumn::make('approval_approved_at')
                    ->label('Disetujui Pada')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approval_rejected_at')
                    ->label('Ditolak Pada')
                    ->dateTime()
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
            'index' => Pages\ListProyeksiLendingApprovals::route('/'),
            'create' => Pages\CreateProyeksiLendingApproval::route('/create'),
            'edit' => Pages\EditProyeksiLendingApproval::route('/{record}/edit'),
        ];
    }
}
