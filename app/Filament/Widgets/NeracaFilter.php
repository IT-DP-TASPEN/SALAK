<?php

namespace App\Filament\Widgets;

use App\Models\BranchOffice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class NeracaFilter extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.neraca-filter';

    protected array|string|int $columnSpan = 'full';

    protected static bool $isLazy = false;

    public ?array $data = [];

    public function form(Form $form): Form
    {
        $user = auth()->user();
        $isHeadOfficeUser = $user && method_exists($user, 'isKantorPusatEmployee') && $user->isKantorPusatEmployee();

        return $form
            ->statePath('data')
            ->schema([
                DatePicker::make('filter_date')
                    ->label('Tanggal')
                    ->default(fn() => now())
                    ->maxDate(today())
                    ->reactive()
                    ->closeOnDateSelection()
                    ->columnSpanFull()
                    ->afterStateUpdated(function (?string $state): void {
                        $this->dispatch(
                            'neracaDateChanged',
                            blank($state) ? null : Carbon::parse($state)->toDateString(),
                        );
                    }),
                Select::make('branch_office')
                    ->label('Kantor Cabang')
                    ->default($isHeadOfficeUser ? null : optional($user?->branchOffice)->branch_code_fincloud)
                    ->visible($isHeadOfficeUser)
                    ->placeholder('Semua Cabang')
                    ->options(
                        fn() => BranchOffice::query()
                            ->orderBy('branch_code_fincloud')
                            ->pluck('branch_name', 'branch_code_fincloud')
                            ->toArray()
                    )
                    ->reactive()
                    ->columnSpanFull()
                    ->afterStateUpdated(function (?string $state): void {
                        $this->dispatch('neracaBranchOfficeChanged', blank($state) ? null : $state);
                    }),
            ]);
    }
}
