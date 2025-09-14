<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashFlowApprovalResource\Pages;
use App\Filament\Resources\CashFlowApprovalResource\RelationManagers;
use App\Models\CashFlowApproval;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CashFlowApprovalResource extends Resource
{
    protected static ?string $model = CashFlowApproval::class;
    protected static ?string $navigationGroup = 'Cash Flow';
    protected static ?string $navigationLabel = 'Approval Cash Flow';
    protected static ?int $navigationSort = 100;
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('approval_cash_flow')
                    ->label('Cash Flow')
                    ->relationship('cashFlow', 'cash_keterangan')
                    ->preload()
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('approval_status')
                    ->label('Status')
                    ->options(function () {
                        $opts = CashFlowApproval::getPossibleEnumValues('approval_status');
                        return array_combine($opts, $opts);
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
                Tables\Columns\TextColumn::make('cashFlow.cash_keterangan')
                    ->label('Cash Flow')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Penyetuju')
                    ->sortable()
                    ->searchable(),
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
            'index' => Pages\ListCashFlowApprovals::route('/'),
            'create' => Pages\CreateCashFlowApproval::route('/create'),
            'edit' => Pages\EditCashFlowApproval::route('/{record}/edit'),
        ];
    }
}
