@foreach ($rows as $row)
    @php
        $children = $row['children'] ?? [];
        $isParent = $children !== [];
    @endphp

    <tr @class(['total' => $row['is_total'], 'parent' => $isParent])>
        <td class="pos">{{ $row['pos'] }}</td>
        <td style="padding-left: {{ 8 + ($depth * 14) }}px">{{ $row['description'] }}</td>
        <td class="amount">{{ $formatCurrency((float) $row['value']) }}</td>
        <td class="amount">{{ $formatCurrency((float) $row['previous_value']) }}</td>
        <td class="percent">{{ $formatPercentage($row['yoy_percent']) }}</td>
    </tr>

    @if ($isParent)
        @include('pdf.partials.financial-report-rows', [
            'rows' => $children,
            'depth' => $depth + 1,
            'formatCurrency' => $formatCurrency,
            'formatPercentage' => $formatPercentage,
        ])
    @endif
@endforeach
