<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentResource\Pages;
use App\Filament\Resources\AgentResource\RelationManagers;
use App\Models\Agent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Agent';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('agent_nama')
                    ->label('Nama Agent')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('agent_mso_code')
                    ->label('Kode MSO')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('agent_jabatan')
                    ->label('Jabatan')
                    ->relationship('jabatan', 'jabatan_nama')
                    ->required(),
                Forms\Components\Select::make('agent_branch_office')
                    ->label('Kantor Cabang')
                    ->relationship('branchOffice', 'branch_name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('agent_nama')
                    ->label('Nama Agent')
                    ->searchable(),
                Tables\Columns\TextColumn::make('agent_mso_code')
                    ->label('Kode MSO')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jabatan.jabatan_nama')
                    ->label('Jabatan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jabatan.jabatan_target')
                    ->label('Target')
                    ->money('IDR', 0, 'id_ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('branchOffice.branch_name')
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
            'index' => Pages\ListAgents::route('/'),
            'create' => Pages\CreateAgent::route('/create'),
            'edit' => Pages\EditAgent::route('/{record}/edit'),
        ];
    }
}
