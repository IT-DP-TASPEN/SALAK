<?php

namespace App\Filament\Widgets;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class TKSDateFilter extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.tks-date-filter';

    protected array|string|int $columnSpan = 'full';

    protected static bool $isLazy = false;

    public ?array $data = [];

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                DatePicker::make('filter_date')
                    ->label('')
                    ->default(fn() => now()->toDateString())
                    ->maxDate(today())
                    ->reactive()
                    ->closeOnDateSelection()
                    ->columnSpanFull()
                    ->afterStateUpdated(function (?string $state): void {
                        $this->dispatch(
                            'rekapTksDateChanged',
                            blank($state) ? null : Carbon::parse($state)->toDateString(),
                        );
                    }),
            ]);
    }
}
