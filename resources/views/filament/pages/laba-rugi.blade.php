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
                    <tbody
                        class="divide-y divide-gray-100 dark:divide-white/5"
                        x-data="{ expanded: {} }"
                    >
                        @include('filament.pages.partials.laba-rugi-rows', [
                            'rows' => $section['rows'],
                            'depth' => 0,
                            'ancestors' => [],
                            'pathPrefix' => '',
                        ])
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endforeach
</x-filament-panels::page>
