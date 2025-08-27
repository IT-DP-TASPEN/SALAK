<?php

namespace App\Filament\Widgets;

use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Filament\Forms\Form;

class ChartTypeFilter extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.chart-type-filter';
    protected array|string|int $columnSpan = 'full';
    protected static ?int $sort = -332;
    public ?array $data = [];

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Select::make('chart_type')
                    ->label('Tipe Grafik')
                    ->options([
                        'realtime' => 'Realtime',
                        'yesterday' => 'Kemarin',
                        'simulation' => 'Simulasi',
                    ])
                    ->reactive()
                    ->columnSpanFull()
                    ->afterStateUpdated(function ($state) {
                        $this->dispatch('chartTypeChanged', $state);
                    })
                    ->selectablePlaceholder(false),
                Select::make('balance_type')
                    ->label('Tipe Saldo')
                    ->options([
                        'effective' => 'Saldo Efektif',
                        'book' => 'Saldo Buku',
                    ])
                    ->reactive()
                    ->columnSpanFull()
                    ->afterStateUpdated(function ($state) {
                        $this->dispatch('balanceTypeChanged', $state);
                    })
                    ->selectablePlaceholder(false),
            ]);
    }
}
