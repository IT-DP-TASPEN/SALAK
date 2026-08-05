<?php

namespace App\Http\Controllers;

use App\Models\BranchOffice;
use App\Services\LabaRugiReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LabaRugiPdfController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $date = $this->normalizeDate($request->query('date'));
        $branchCode = $this->resolveBranchCode($request);

        $sections = app(LabaRugiReportService::class)->build($date, $branchCode);

        return Pdf::loadView('pdf.financial-report', [
            'reportTitle' => 'Laba Rugi',
            'sections' => $sections,
            'date' => $date,
            'branchLabel' => $this->branchLabel($branchCode),
        ])
            ->setPaper('a4', 'landscape')
            ->download($this->filename($date, $branchCode));
    }

    private function resolveBranchCode(Request $request): ?string
    {
        $user = $request->user();

        if (! ($user && method_exists($user, 'isKantorPusatEmployee') && $user->isKantorPusatEmployee())) {
            return optional($user?->branchOffice)->branch_code_fincloud;
        }

        $branchCode = $request->query('branch');
        $branchCode = is_string($branchCode) ? trim($branchCode) : null;

        return blank($branchCode) ? null : $branchCode;
    }

    private function branchLabel(?string $branchCode): string
    {
        if (blank($branchCode)) {
            return 'Semua Cabang';
        }

        $branchName = BranchOffice::query()
            ->where('branch_code_fincloud', $branchCode)
            ->value('branch_name');

        return $branchName ? "{$branchCode} - {$branchName}" : $branchCode;
    }

    private function filename(string $date, ?string $branchCode): string
    {
        $branchSegment = blank($branchCode) ? 'semua-cabang' : $branchCode;

        return "laba-rugi-{$date}-{$branchSegment}.pdf";
    }

    private function normalizeDate(mixed $date): string
    {
        $asOf = blank($date) ? Carbon::today() : Carbon::parse((string) $date)->startOfDay();

        if ($asOf->isFuture()) {
            $asOf = Carbon::today();
        }

        return $asOf->toDateString();
    }
}
