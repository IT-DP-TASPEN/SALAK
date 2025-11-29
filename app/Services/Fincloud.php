<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class Fincloud
{
    private string $sessionId = '';

    public function __construct(
        private string $baseUrl = 'https://172.20.57.7/fincloud-taspen-web',
        private string $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:144.0) Gecko/20100101 Firefox/144.0',
        private int    $timeoutSeconds = 120,
        private bool   $verifyTls = false,
    ) {}

    public function getBranches(): array
    {
        $http = Http::withHeaders([
            'User-Agent' => $this->userAgent,
        ])
            ->baseUrl($this->baseUrl)
            ->withOptions([
                'timeout' => $this->timeoutSeconds,
                'verify' => $this->verifyTls,
            ]);

        $response = $http->get('/admin/access/listvalues');
        if (!$response->ok()) {
            throw new \RuntimeException(
                sprintf('getBranches failed : %s %s', $response->status(), $response->reason())
            );
        }

        $payload = $response->json();
        $status  = $payload['status'] ?? null;
        if ($status !== 'ok') {
            throw new \RuntimeException('getBranches failed with unknown error');
        }

        $branches = $payload['data']['result']['locationid'] ?? [];
        if (!is_array($branches) || empty($branches)) {
            throw new \RuntimeException('getBranches failed: invalid data format');
        }

        return array_map(fn($branch) => $branch['id'] ?? null, $branches);
    }

    public function login(string $username, string $password, string $role = 'R-0041', string $location = '000'): void
    {
        $response = Http::asForm()
            ->baseUrl($this->baseUrl)
            ->withHeaders([
                'User-Agent' => $this->userAgent,
            ])
            ->withOptions([
                'timeout' => $this->timeoutSeconds,
                'verify' => $this->verifyTls,
            ])
            ->post('/admin/access/login', [
                'locationid' => $location,
                'roleid'     => $role,
                'username'   => $username,
                'pwd'        => $password,
            ]);

        if (!$response->ok()) {
            throw new \RuntimeException(
                sprintf('login failed : %s %s', $response->status(), $response->reason())
            );
        }

        $payload = $response->json();
        $status  = $payload['status'] ?? null;

        if ($status !== 'ok') {
            $systemError = $payload['error']['system'] ?? null;

            if (!empty($systemError)) {
                throw new \RuntimeException('login error: ' . $systemError);
            }

            throw new \RuntimeException('login failed with unknown error');
        }

        $result = $payload['data']['result'] ?? [];
        $sessionId = $result['sessionid'] ?? null;
        if (empty($sessionId)) {
            throw new \RuntimeException('login failed: session ID not found');
        }

        $this->sessionId = $sessionId;
    }

    public function inquiryCIF(string $cifNumber): array
    {
        return $this->requestWithSession(
            method: 'GET',
            path: '/cif/inquiry/cif/cif',
            query: ['nocif' => $cifNumber],
        );
    }

    public function inquiryDetailOutstandingReport(Carbon $asOf): array
    {
        $branches = $this->getBranches();

        $responses = [];
        foreach ($branches as $branch) {
            $response = $this->downloadReportFile(
                path: sprintf(
                    '/app/report/daily/%s',
                    $asOf->format('Ymd'),
                ),
                file: sprintf(
                    'DetailOutstandingRekeningPinjaman_%s.csv',
                    $branch,
                ),
            );

            $responses[$branch] = $response;
        }

        $header = null;
        foreach ($responses as $response) {
            $line = strtok($response, "\n");
            if (is_null($header)) {
                $header = $line;
            } elseif ($header !== $line) {
                throw new \RuntimeException('inconsistent CSV header across branches');
            }
        }

        return $responses;
    }

    public function inquiryBalanceSheetReport(Carbon $asOf, ?string $branch = null): string
    {
        return $this->requestWithSession(
            method: 'GET',
            path: '/system/laporanUmum/data/lap',
            raw: true,
            query: [
                'nm' => 'Balance Sheet Report csv',
                'type' => 'csv',
                'p' => json_encode([is_null($branch) ? '' : $branch, $asOf->format('Y-n-j')]),
            ],
            formBody: [
                'sessionId' => $this->sessionId,
            ],
        );
    }


    public function downloadReportFile(string $path, string $file): string
    {
        return $this->requestWithSession(
            method: 'GET',
            path: '/system/downloaderlaporan/download.php',
            raw: true,
            query: [
                'file' => $file,
                'path' => $path,
            ],
        );
    }

    private function requestWithSession(
        string $method,
        string $path,
        bool $raw = false,
        array $query = [],
        array $formBody = []
    ): array|string {
        $http = Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'sessionid'  => $this->sessionId,
        ])
            ->baseUrl($this->baseUrl)
            ->withOptions([
                'timeout'  => $this->timeoutSeconds,
                'verify'   => $this->verifyTls,
            ]);


        if (!empty($query)) {
            $http = $http->withQueryParameters($query);
        }

        if (!empty($formBody)) {
            $http = $http->asForm();
        }

        $response = match (strtoupper($method)) {
            'GET'  => $http->get($path),
            'POST' => $http->post($path, $formBody),
            default => throw new \InvalidArgumentException("Unsupported method [$method]"),
        };

        if (!$response->ok()) {
            throw new \RuntimeException(
                sprintf('core request failed: %s %s', $response->status(), $response->reason())
            );
        }

        return $raw ? $response->body() : $response->json();
    }
}
