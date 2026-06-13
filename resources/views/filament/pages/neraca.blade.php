<x-filament-panels::page>
    @foreach ($sections as $section)
        <x-filament::section :heading="$section['label']">
            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-[76rem] table-fixed divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <colgroup>
                        <col class="w-40">
                        <col>
                        <col class="w-48">
                        <col class="w-48">
                        <col class="w-28">
                    </colgroup>
                    <thead>
                        <tr class="text-left text-gray-600 dark:text-gray-300">
                            <th class="px-4 py-3 font-semibold">Pos</th>
                            <th class="px-4 py-3 font-semibold">Description</th>
                            <th class="px-4 py-3 text-right font-semibold">Saldo</th>
                            <th class="px-4 py-3 text-right font-semibold">Saldo Tahun Lalu</th>
                            <th class="px-4 py-3 text-right font-semibold">YoY%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($section['rows'] as $row)
                            <tr @class([
                                'bg-gray-50/80 dark:bg-white/5' => $row['is_total'],
                            ])>
                                <td @class([
                                    'px-4 py-3 align-top whitespace-nowrap text-gray-900 dark:text-white',
                                    'font-semibold' => $row['is_total'],
                                ])>
                                    {{ $row['pos'] }}
                                </td>
                                <td @class([
                                    'px-4 py-3 align-top text-gray-700 dark:text-gray-200',
                                    'font-semibold text-gray-900 dark:text-white' => $row['is_total'],
                                ])>
                                    {{ $row['description'] }}
                                </td>
                                <td @class([
                                    'px-4 py-3 text-right tabular-nums whitespace-nowrap text-gray-900 dark:text-white',
                                    'font-semibold' => $row['is_total'],
                                ])>
                                    {{ $this->formatCurrency($row['value']) }}
                                </td>
                                <td @class([
                                    'px-4 py-3 text-right tabular-nums whitespace-nowrap text-gray-900 dark:text-white',
                                    'font-semibold' => $row['is_total'],
                                ])>
                                    {{ $this->formatCurrency($row['previous_value']) }}
                                </td>
                                <td @class([
                                    'px-4 py-3 text-right tabular-nums whitespace-nowrap text-gray-900 dark:text-white',
                                    'font-semibold' => $row['is_total'],
                                ])>
                                    {{ $this->formatPercentage($row['yoy_percent']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endforeach
</x-filament-panels::page>
