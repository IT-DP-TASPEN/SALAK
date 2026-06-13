<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Neraca {{ $date }}</title>
    <style>
        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.3;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 8px;
        }

        .meta {
            color: #374151;
            margin-bottom: 16px;
        }

        .section {
            margin-bottom: 18px;
        }

        .section-title {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            font-size: 13px;
            font-weight: 700;
            page-break-after: avoid;
            padding: 7px 9px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            vertical-align: top;
        }

        th {
            background: #f9fafb;
            color: #374151;
            font-weight: 700;
            text-align: left;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .pos {
            width: 105px;
            white-space: nowrap;
        }

        .amount {
            text-align: right;
            white-space: nowrap;
            width: 118px;
        }

        .percent {
            text-align: right;
            white-space: nowrap;
            width: 58px;
        }

        .total td {
            background: #f3f4f6;
            font-weight: 700;
        }
    </style>
</head>
<body>
    @php
        $formatCurrency = fn (float $value): string => 'Rp '.number_format($value, 0, ',', '.');
        $formatPercentage = fn (?float $value): string => $value === null ? '-' : number_format($value, 2, ',', '.').'%' ;
    @endphp

    <h1>Neraca</h1>
    <div class="meta">
        Tanggal: {{ $date }}<br>
        Kantor Cabang: {{ $branchLabel }}<br>
        Saldo nol: {{ $showZeroBalances ? 'Ditampilkan' : 'Disembunyikan' }}
    </div>

    @foreach ($sections as $section)
        <div class="section">
            <div class="section-title">{{ $section['label'] }}</div>
            <table>
                <thead>
                    <tr>
                        <th class="pos">Pos</th>
                        <th>Description</th>
                        <th class="amount">Saldo</th>
                        <th class="amount">Saldo Tahun Lalu</th>
                        <th class="percent">YoY%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($section['rows'] as $row)
                        <tr @class(['total' => $row['is_total']])>
                            <td class="pos">{{ $row['pos'] }}</td>
                            <td>{{ $row['description'] }}</td>
                            <td class="amount">{{ $formatCurrency((float) $row['value']) }}</td>
                            <td class="amount">{{ $formatCurrency((float) $row['previous_value']) }}</td>
                            <td class="percent">{{ $formatPercentage($row['yoy_percent']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
