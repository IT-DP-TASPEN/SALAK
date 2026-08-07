@foreach ($rows as $index => $row)
    @php
        $path = $pathPrefix === '' ? (string) $index : $pathPrefix.'.'.$index;
        $children = $row['children'] ?? [];
        $isParent = $children !== [];
        $visibility = implode(' && ', array_map(
            fn (string $ancestor): string => "expanded['{$ancestor}'] !== false",
            $ancestors,
        ));
    @endphp

    <tr
        @if ($visibility !== '') x-show="{{ $visibility }}" @endif
        @class(['bg-gray-50/80 dark:bg-white/5' => $row['is_total']])
    >
        <td @class([
            'px-4 py-3 align-top whitespace-nowrap text-gray-900 dark:text-white',
            'font-semibold' => $row['is_total'] || $isParent,
        ])>
            {{ $row['pos'] }}
        </td>
        <td
            style="padding-left: {{ 1 + ($depth * 1.25) }}rem"
            @class([
                'py-3 pr-4 align-top text-gray-700 dark:text-gray-200',
                'font-semibold text-gray-900 dark:text-white' => $row['is_total'] || $isParent,
            ])
        >
            <div class="flex items-start gap-1">
                @if ($isParent)
                    <button
                        type="button"
                        class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                        x-on:click="expanded['{{ $path }}'] = expanded['{{ $path }}'] === false"
                        x-bind:aria-expanded="expanded['{{ $path }}'] !== false"
                        aria-label="Tampilkan atau sembunyikan {{ $row['description'] }}"
                    >
                        <span aria-hidden="true" x-text="expanded['{{ $path }}'] === false ? '▸' : '▾'">▾</span>
                    </button>
                @else
                    <span class="inline-block w-5 shrink-0" aria-hidden="true"></span>
                @endif

                <span>{{ $row['description'] }}</span>
            </div>
        </td>
        <td @class([
            'px-4 py-3 text-right tabular-nums whitespace-nowrap text-gray-900 dark:text-white',
            'font-semibold' => $row['is_total'] || $isParent,
        ])>
            {{ $this->formatCurrency($row['value']) }}
        </td>
        <td @class([
            'px-4 py-3 text-right tabular-nums whitespace-nowrap text-gray-900 dark:text-white',
            'font-semibold' => $row['is_total'] || $isParent,
        ])>
            {{ $this->formatCurrency($row['previous_value']) }}
        </td>
        <td @class([
            'px-4 py-3 text-right tabular-nums whitespace-nowrap text-gray-900 dark:text-white',
            'font-semibold' => $row['is_total'] || $isParent,
        ])>
            {{ $this->formatPercentage($row['yoy_percent']) }}
        </td>
    </tr>

    @if ($isParent)
        @include('filament.pages.partials.laba-rugi-rows', [
            'rows' => $children,
            'depth' => $depth + 1,
            'ancestors' => [...$ancestors, $path],
            'pathPrefix' => $path,
        ])
    @endif
@endforeach
