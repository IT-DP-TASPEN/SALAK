<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProyeksiFundingApprovalResource\Pages;
use App\Filament\Resources\ProyeksiFundingApprovalResource\RelationManagers;
use App\Models\ProyeksiFundingApproval;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProyeksiFundingApprovalResource extends Resource
{
    protected static ?string $model = ProyeksiFundingApproval::class;
    protected static ?string $navigationGroup = 'Proyeksi Funding';
    protected static ?string $navigationLabel = 'Approval Proyeksi Funding';
    protected static ?int $navigationSort = 100;
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('approval_funding')
                    ->label('Lending')
                    ->relationship('proyeksiFunding', 'funding_nasabah_nama')
                    ->preload()
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('approval_status')
                    ->label('Status')
                    ->options(function () {
                        $options = ProyeksiFundingApproval::getPossibleEnumValues('approval_status');
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
                Tables\Columns\TextColumn::make('proyeksiFunding.funding_nasabah_nama')
                    ->label('Funding')
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
            'index' => Pages\ListProyeksiFundingApprovals::route('/'),
            'create' => Pages\CreateProyeksiFundingApproval::route('/create'),
            'edit' => Pages\EditProyeksiFundingApproval::route('/{record}/edit'),
        ];
    }
}
