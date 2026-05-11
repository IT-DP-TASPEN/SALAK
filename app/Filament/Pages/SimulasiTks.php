<?php

namespace App\Filament\Pages;

use App\Models\BranchOffice;
use App\Models\LoanOutstanding;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\RawJs;

class SimulasiTks extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Simulasi TKS';
    protected static ?string $title = 'Simulasi TKS';
    protected static string $view = 'filament.pages.simulasi-tks';

    public ?array $data = [];
    public ?array $simulation = null;

    private $selectedRatio = [
        // 'KPMM' => [
        //     'Modal Inti' => 0.00,
        //     'Modal Pelengkap' => 0.00,
        //     'Aset Tertimbang Menurut Risiko (ATMR)' => 0.00,
        // ],
        'CKPN per PPKA' => [
            'CKPN' => 0.00,
            'PPKA' => 0.00,
        ],
        'NPL Nett' => [
            'Bad Loans' => 0.00,
            'Total Loans' => 0.00,
            'CKPN' => 0.00,
        ],
        'NPL Gross' => [
            'Bad Loans' => 0.00,
            'Total Loans' => 0.00,
        ],
        'ROA' => [
            'Laba Bersih Sebelum Pajak' => 0.00,
            'Total Asset' => 0.00,
        ],
        'BOPO' => [
            'Beban Operasional' => 0.00,
            'Pendapatan Operasional' => 0.00,
        ],
        'LDR' => [
            'Total Loans' => 0.00,
            'Total Deposits' => 0.00,
            'NPL (%)' => 0.00,
        ],
        'NIM' => [
            'Pendapatan Bunga Bersih' => 0.00,
            'Rata-rata Aset Produktif' => 0.00,
        ],
        'Cash Ratio' => [
            'Aset Likuid' => 0.00,
            'Kewajiban Lancar' => 0.00,
        ],
    ];

    private array $optionalComponents = [
        'LDR' => [
            'NPL (%)',
        ],
    ];

    public function mount(): void
    {
        $defaultRatio = array_key_first($this->selectedRatio);
        $defaultTanggal = Carbon::today()->toDateString();
        $defaultComponents = $this->defaultComponentsForRatio($defaultRatio, $defaultTanggal);
        $this->selectedRatio[$defaultRatio] = array_replace(
            $this->selectedRatio[$defaultRatio],
            $defaultComponents
        );

        $this->form->fill([
            'ratio' => $defaultRatio,
            'tanggal' => $defaultTanggal,
            'components' => $defaultComponents,
        ]);

        $this->simulation = $this->calculateSimulation($defaultRatio, $defaultComponents);
    }

    public function form(Form $form): Form
    {
        $defaultRatio = array_key_first($this->selectedRatio);
        $defaultTanggal = Carbon::today()->toDateString();
        $ratioOptions = array_combine(
            array_keys($this->selectedRatio),
            array_keys($this->selectedRatio)
        );

        return $form
            ->statePath('data')
            ->schema([
                Section::make('Simulasi TKS')
                    ->schema([
                        DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->default($defaultTanggal)
                            ->maxDate(Carbon::today())
                            ->reactive()
                            ->afterStateUpdated(function ($state, Get $get, callable $set) use ($defaultRatio) {
                                $ratio = $get('ratio') ?: $defaultRatio;
                                if (!$ratio || !isset($this->selectedRatio[$ratio])) {
                                    $set('components', []);
                                    $this->simulation = null;
                                    return;
                                }

                                $defaults = $this->defaultComponentsForRatio($ratio, $state);
                                $set('components', $defaults);
                                $this->simulation = $this->calculateSimulation($ratio, $defaults);
                            }),
                        Select::make('ratio')
                            ->label('Pilih Rasio')
                            ->options($ratioOptions)
                            ->default($defaultRatio)
                            ->reactive()
                            ->afterStateUpdated(function ($state, Get $get, callable $set) {
                                if (!$state || !isset($this->selectedRatio[$state])) {
                                    $set('components', []);
                                    $this->simulation = null;
                                    return;
                                }

                                $defaults = $this->defaultComponentsForRatio($state, $get('tanggal'));
                                // $this->selectedRatio[$state] = array_replace(
                                //     $this->selectedRatio[$state],
                                //     $defaults
                                // );
                                $set('components', $defaults);
                                $this->simulation = $this->calculateSimulation($state, $defaults);
                            }),
                    ])->columns(1),
                Section::make('Komponen Rasio')
                    ->key(fn(Get $get) => 'components-' . ($get('ratio') ?? $defaultRatio))
                    ->schema(function (Get $get) use ($defaultRatio): array {
                        $ratio = $get('ratio') ?: $defaultRatio;
                        $components = $this->selectedRatio[$ratio] ?? [];
                        $optional = $this->optionalComponents[$ratio] ?? [];

                        return array_map(
                            function ($key) use ($components, $optional) {
                                $isRequired = !in_array($key, $optional, true);

                                return TextInput::make("components.$key")
                                    ->label($key)
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('Rp')
                                    // ->mask(RawJs::make('$money($input, \'.\', \',\', 2)'))
                                    ->default($components[$key] ?? 0.00)
                                    ->required($isRequired)
                                    ->live()
                                    ->afterStateUpdated(fn() => $this->simulate());
                            },
                            array_keys($components)
                        );
                    })
                    ->columns(1),
            ]);
    }

    public function simulate(): void
    {
        $data = $this->form->getRawState();
        $ratio = $data['ratio'] ?? null;
        $components = $data['components'] ?? [];

        $this->simulation = $this->calculateSimulation($ratio, $components);
    }

    private function calculateSimulation(?string $ratio, array $components): ?array
    {
        if (!$ratio || !isset($this->selectedRatio[$ratio])) {
            return null;
        }

        $value = match ($ratio) {
            'KPMM' => $this->calculateKpmm($components),
            'CKPN per PPKA' => $this->ratioValue('CKPN', 'PPKA', $components),
            'NPL Nett' => $this->calculateNplNett($components),
            'NPL Gross' => $this->ratioValue('Bad Loans', 'Total Loans', $components),
            'ROA' => $this->ratioValue('Laba Bersih Sebelum Pajak', 'Total Asset', $components),
            'BOPO' => $this->ratioValue('Beban Operasional', 'Pendapatan Operasional', $components),
            'LDR' => $this->ratioValue('Total Loans', 'Total Deposits', $components),
            'NIM' => $this->ratioValue('Pendapatan Bunga Bersih', 'Rata-rata Aset Produktif', $components),
            'Cash Ratio' => $this->ratioValue('Aset Likuid', 'Kewajiban Lancar', $components),
            default => 0.0,
        };

        $interpretation = $this->interpretationFor($ratio, $value, $components);

        return [
            'ratio' => $ratio,
            'value' => $value,
            'formatted' => $this->formatPercent($value),
            'status' => $interpretation['description'] ?? null,
            'color' => $interpretation['color'] ?? 'gray',
            'requirement' => $interpretation['requirement'] ?? null,
        ];
    }

    private function interpretationFor(string $ratio, float $value, array $components): array
    {
        return match ($ratio) {
            'CKPN per PPKA' => $this->interpretCkpnPerPpka($value),
            'ROA' => $this->interpretRoa($value),
            'NIM' => $this->interpretNim($value),
            'Cash Ratio' => $this->interpretCashRatio($value),
            'NPL Gross' => $this->interpretNpl($value),
            'NPL Nett' => $this->interpretNplNett($value),
            'BOPO' => $this->interpretBopo($value),
            'LDR' => $this->interpretLdr($value, $components),
            default => [],
        };
    }

    private function interpretCkpnPerPpka(float $value): array
    {
        return match (true) {
            $value < 50 => [
                'color' => 'success',
                'description' => 'Sehat',
                'requirement' => '< 50%',
            ],
            $value < 100 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
                'requirement' => '< 50%',
            ],
            default => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
                'requirement' => '< 50%',
            ],
        };
    }

    private function interpretRoa(float $value): array
    {
        return match (true) {
            $value >= 2.0 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
                'requirement' => '>= 2%',
            ],
            $value >= 1.5 => [
                'color' => 'success',
                'description' => 'Sehat',
                'requirement' => '>= 2%',
            ],
            $value >= 1.0 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
                'requirement' => '>= 2%',
            ],
            $value >= 0.5 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
                'requirement' => '>= 2%',
            ],
            default => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
                'requirement' => '>= 2%',
            ],
        };
    }

    private function interpretNim(float $value): array
    {
        return match (true) {
            $value >= 10 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
                'requirement' => '>= 10%',
            ],
            $value >= 8.0 => [
                'color' => 'success',
                'description' => 'Sehat',
                'requirement' => '>= 10%',
            ],
            $value >= 6.0 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
                'requirement' => '>= 10%',
            ],
            $value >= 4.0 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
                'requirement' => '>= 10%',
            ],
            default => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
                'requirement' => '>= 10%',
            ],
        };
    }

    private function interpretCashRatio(float $value): array
    {
        return match (true) {
            $value >= 4.05 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
                'requirement' => '>= 4.05%',
            ],
            $value >= 3.30 => [
                'color' => 'success',
                'description' => 'Sehat',
                'requirement' => '>= 4.05%',
            ],
            $value >= 2.55 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
                'requirement' => '>= 4.05%',
            ],
            $value >= 1.8 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
                'requirement' => '>= 4.05%',
            ],
            default => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
                'requirement' => '>= 4.05%',
            ],
        };
    }

    private function interpretNpl(float $value): array
    {
        return match (true) {
            $value <= 5 => [
                'color' => 'success',
                'description' => 'NPL Tidak signifikan',
                'requirement' => '<= 5%',
            ],
            $value <= 6 => [
                'color' => 'warning',
                'description' => 'NPL Kurang signifikan',
                'requirement' => '<= 5%',
            ],
            $value <= 7 => [
                'color' => 'danger',
                'description' => 'NPL Cukup signifikan',
                'requirement' => '<= 5%',
            ],
            default => [
                'color' => 'danger',
                'description' => 'NPL Sangat signifikan',
                'requirement' => '<= 5%',
            ],
        };
    }

    private function interpretNplNett(float $value): array
    {
        return match (true) {
            $value <= 2 => [
                'color' => 'success',
                'description' => 'NPL Nett Tidak signifikan',
                'requirement' => '<= 5%',
            ],
            $value <= 3 => [
                'color' => 'warning',
                'description' => 'NPL Nett Kurang signifikan',
                'requirement' => '<= 5%',
            ],
            $value <= 4 => [
                'color' => 'danger',
                'description' => 'NPL Nett Cukup signifikan',
                'requirement' => '<= 5%',
            ],
            default => [
                'color' => 'danger',
                'description' => 'NPL Nett Sangat signifikan',
                'requirement' => '<= 5%',
            ],
        };
    }

    private function interpretBopo(float $value): array
    {
        return match (true) {
            $value <= 85 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
                'requirement' => '<= 85%',
            ],
            $value <= 90 => [
                'color' => 'success',
                'description' => 'Sehat',
                'requirement' => '<= 85%',
            ],
            $value <= 95 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
                'requirement' => '<= 85%',
            ],
            $value <= 100 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
                'requirement' => '<= 85%',
            ],
            default => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
                'requirement' => '<= 85%',
            ],
        };
    }

    private function interpretLdr(float $value, array $components): array
    {
        if (!array_key_exists('NPL (%)', $components)) {
            return [
                'color' => 'gray',
                'description' => 'Butuh NPL untuk interpretasi LDR',
                'requirement' => '<= 90%',
            ];
        }

        $npl = $this->getComponentValue($components, 'NPL (%)');

        return match (true) {
            $value <= 90 => [
                'color' => 'success',
                'description' => 'Sangat sehat',
                'requirement' => '<= 90%',
            ],
            $value > 90 && $npl <= 5 => [
                'color' => 'success',
                'description' => 'Sehat',
                'requirement' => '<= 90%',
            ],
            $value > 90 && $npl <= 6 => [
                'color' => 'warning',
                'description' => 'Cukup sehat',
                'requirement' => '<= 90%',
            ],
            $value > 90 && $npl <= 7 => [
                'color' => 'danger',
                'description' => 'Tidak sehat',
                'requirement' => '<= 90%',
            ],
            default => [
                'color' => 'danger',
                'description' => 'Sangat tidak sehat',
                'requirement' => '<= 90%',
            ],
        };
    }

    private function calculateKpmm(array $components): float
    {
        $modalInti = $this->getComponentValue($components, 'Modal Inti');
        $modalPelengkap = $this->getComponentValue($components, 'Modal Pelengkap');
        $atmr = $this->getComponentValue($components, 'Aset Tertimbang Menurut Risiko (ATMR)');

        $modal = $modalInti + $modalPelengkap;
        if ($modal == 0.0 || $atmr == 0.0) {
            return 0.0;
        }

        return $modal / $atmr * 100;
    }

    private function calculateNplNett(array $components): float
    {
        $badLoans = $this->getComponentValue($components, 'Bad Loans');
        $totalLoans = $this->getComponentValue($components, 'Total Loans');
        $ckpn = $this->getComponentValue($components, 'CKPN');

        if ($totalLoans == 0.0) {
            return 0.0;
        }

        return ($badLoans - $ckpn) / $totalLoans * 100;
    }

    private function ratioValue(string $numeratorKey, string $denominatorKey, array $components): float
    {
        $numerator = $this->getComponentValue($components, $numeratorKey);
        $denominator = $this->getComponentValue($components, $denominatorKey);

        if ($denominator == 0.0) {
            return 0.0;
        }

        return $numerator / $denominator * 100;
    }

    private function getComponentValue(array $components, string $key): float
    {
        $value = $components[$key] ?? 0.0;

        return $this->parseLocalizedNumber($value);
    }

    private function formatPercent(float $value): string
    {
        return number_format($value, 2, ',', '.') . '%';
    }

    private function parseLocalizedNumber(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return 0.0;
        }

        $normalized = str_replace(['.', ' '], '', $value);
        $normalized = str_replace(',', '.', $normalized);
        $normalized = preg_replace('/[^0-9\.\-]/', '', $normalized) ?? '';

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function defaultComponentsForRatio(string $ratio, ?string $tanggal = null): array
    {
        $defaults = $this->selectedRatio[$ratio] ?? [];
        if ($defaults === []) {
            return [];
        }

        $asOf = $this->resolveAsOf($tanggal);
        $tanggal = $asOf->toDateString();
        $branch = $this->getBranchCode();

        $computed = match ($ratio) {
            'KPMM' => [
                'Modal Inti' => BranchOffice::konsolidasiModalInti($tanggal, $branch),
                'Modal Pelengkap' => BranchOffice::konsolidasiModalPelengkap($tanggal, $branch),
                'Aset Tertimbang Menurut Risiko (ATMR)' => BranchOffice::konsolidasiATMR($tanggal, $branch),
            ],
            'CKPN per PPKA' => [
                'CKPN' => $this->getCkpn($tanggal, $branch),
                'PPKA' => BranchOffice::konsolidasiPPKA($tanggal, $branch),
            ],
            'NPL Nett' => $this->defaultNplComponents($tanggal, $branch, true),
            'NPL Gross' => $this->defaultNplComponents($tanggal, $branch, false),
            'ROA' => $this->defaultRoaComponents($asOf, $branch),
            'BOPO' => $this->defaultBopoComponents($asOf, $branch),
            'LDR' => $this->defaultLdrComponents($tanggal, $branch),
            'NIM' => $this->defaultNimComponents($asOf, $branch),
            'Cash Ratio' => [
                'Aset Likuid' => BranchOffice::konsolidasiAssetLiquid($tanggal, false, true, $branch),
                'Kewajiban Lancar' => BranchOffice::konsolidasiKewajibanLancar($tanggal, $branch),
            ],
            default => [],
        };

        return array_replace($defaults, $computed);
    }

    private function resolveAsOf(?string $tanggal): Carbon
    {
        if (!$tanggal) {
            return Carbon::today();
        }

        try {
            return Carbon::parse($tanggal);
        } catch (\Throwable) {
            return Carbon::today();
        }
    }

    private function getBranchCode(): ?string
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        if (method_exists($user, 'isKantorPusatEmployee') && $user->isKantorPusatEmployee()) {
            return null;
        }

        return optional($user->branchOffice)->branch_code_fincloud;
    }

    private function getCkpn(string $tanggal, ?string $branch): float
    {
        return array_sum(BranchOffice::saldoNeraca2(['1272005'], $branch, $tanggal));
    }

    private function defaultNplComponents(string $tanggal, ?string $branch, bool $withCkpn): array
    {
        [$badLoans, $totalLoans] = $this->getLoanTotals($tanggal, $branch);

        $components = [
            'Bad Loans' => $badLoans,
            'Total Loans' => $totalLoans,
        ];

        if ($withCkpn) {
            $components['CKPN'] = $this->getCkpn($tanggal, $branch);
        }

        return $components;
    }

    private function getLoanTotals(string $tanggal, ?string $branch): array
    {
        $baseQuery = LoanOutstanding::query()
            ->when($branch, fn($q) => $q->where('loan_branch_office', $branch))
            ->where('loan_date_params', $tanggal);

        $badLoans = (clone $baseQuery)
            ->whereIn('loan_bi_collectability', [3, 4, 5])
            ->sum('loan_outstanding');

        $totalLoans = (clone $baseQuery)->sum('loan_outstanding');

        return [(float) $badLoans, (float) $totalLoans];
    }

    private function defaultRoaComponents(Carbon $asOf, ?string $branch): array
    {
        $month = $asOf->month;

        if ($asOf->isBefore('2025-10-12')) {
            $labaBeforeTax = array_sum(
                BranchOffice::saldoNeracaMso(
                    ['2.330'],
                    $branch,
                    $asOf->toDateString()
                )
            );
        } else {
            $labaBeforeTax = array_sum(
                BranchOffice::saldoNeraca2(
                    ['323', '558'],
                    $branch,
                    $asOf->toDateString()
                )
            );
        }

        $totalAsset = 0.0;
        for ($m = 1; $m <= $month; $m++) {
            $tgl = $asOf->copy()->setMonth($m);
            if ($tgl->isBefore('2025-10-12')) {
                $aktiva = array_sum(
                    BranchOffice::saldoNeracaMso(
                        ['1.190'],
                        $branch,
                        $tgl->toDateString()
                    )
                );
                $aka = array_sum(
                    BranchOffice::saldoNeracaMso(
                        ['1.170'],
                        $branch,
                        $tgl->toDateString()
                    )
                );
                $totalAsset += $aktiva - $aka;
            } else {
                $totalAsset += array_sum(
                    BranchOffice::saldoNeraca2(
                        ['1'],
                        $branch,
                        $tgl->toDateString()
                    )
                );
            }
        }

        $monthsInYear = 12;
        $pendapatanBersih = $month ? $labaBeforeTax / $month * $monthsInYear : 0.0;
        $rataRataTotalAset = $month ? $totalAsset / $month : 0.0;

        return [
            'Laba Bersih Sebelum Pajak' => $pendapatanBersih,
            'Total Asset' => $rataRataTotalAset,
        ];
    }

    private function defaultBopoComponents(Carbon $asOf, ?string $branch): array
    {
        $month = $asOf->month;
        $tanggal = $asOf->toDateString();

        $bebanOperasional = BranchOffice::konsolidasiBebanOperasional($tanggal, $branch);
        $pendapatanOperasional = BranchOffice::konsolidasiPendapatanOperasional($tanggal, $branch);

        $monthsInYear = 12;
        $bebanOperasional = $month ? $bebanOperasional / $month * $monthsInYear : 0.0;
        $pendapatanOperasional = $month ? $pendapatanOperasional / $month * $monthsInYear : 0.0;

        return [
            'Beban Operasional' => $bebanOperasional,
            'Pendapatan Operasional' => $pendapatanOperasional,
        ];
    }

    private function defaultLdrComponents(string $tanggal, ?string $branch): array
    {
        $totalLoans = array_sum(BranchOffice::saldoNeraca2(['121'], $branch, $tanggal));
        $totalDeposits = array_sum(BranchOffice::saldoNeraca2(['221', '2312200', '2312201'], $branch, $tanggal));

        return [
            'Total Loans' => $totalLoans,
            'Total Deposits' => $totalDeposits,
            'NPL (%)' => BranchOffice::konsolidasiNPL($tanggal, $branch),
        ];
    }

    private function defaultNimComponents(Carbon $asOf, ?string $branch): array
    {
        $month = $asOf->month;
        $pendapatanBunga = 0.0;
        $bebanBunga = 0.0;
        $asetProduktif = 0.0;

        if ($asOf->isBefore('2025-10-12')) {
            $pendapatanBunga = array_sum(
                BranchOffice::saldoNeracaMso(
                    ['2.112', '2.113', '2.115', '2.120.1'],
                    $branch,
                    $asOf->toDateString()
                )
            );
            $bebanBunga = array_sum(
                BranchOffice::saldoNeracaMso(
                    ['2.171', '2.172', '2.166', '2.167', '2.168'],
                    $branch,
                    $asOf->toDateString()
                )
            );
        } else {
            $pendapatanBunga = array_sum(
                BranchOffice::saldoNeraca2(
                    ['401', '402', '403', '410'],
                    $branch,
                    $asOf->toDateString()
                )
            );
            $bebanBunga = array_sum(
                BranchOffice::saldoNeraca2(
                    ['501', '502', '511'],
                    $branch,
                    $asOf->toDateString()
                )
            );
        }

        for ($m = 1; $m <= $month; $m++) {
            $tanggal = $asOf->copy()->setMonth($m);
            if ($tanggal->isBefore('2025-10-12')) {
                $asetProduktif += array_sum(
                    BranchOffice::saldoNeracaMso(
                        ['1.120', '1.130.1'],
                        $branch,
                        $tanggal->toDateString()
                    )
                );
            } else {
                $asetProduktif += array_sum(
                    BranchOffice::saldoNeraca2(
                        ['110', '121'],
                        $branch,
                        $tanggal->toDateString()
                    )
                );
            }
        }

        $monthsInYear = 12;
        $pendapatanBunga = $month ? $pendapatanBunga / $month * $monthsInYear : 0.0;
        $bebanBunga = $month ? $bebanBunga / $month * $monthsInYear : 0.0;
        $pendapatanBungaBersih = $pendapatanBunga - $bebanBunga;
        $asetProduktif = $month ? $asetProduktif / $month : 0.0;

        return [
            'Pendapatan Bunga Bersih' => $pendapatanBungaBersih,
            'Rata-rata Aset Produktif' => $asetProduktif,
        ];
    }
}
