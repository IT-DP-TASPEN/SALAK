<x-filament-panels::page>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    @php
        $colorClasses = [
            'success' => 'text-emerald-600 dark:text-emerald-400',
            'warning' => 'text-amber-600 dark:text-amber-400',
            'danger' => 'text-rose-600 dark:text-rose-400',
            'gray' => 'text-gray-600 dark:text-gray-400',
        ];
    @endphp

    <div class="space-y-6">
        {{ $this->form }}

        @if ($this->simulation)
            <x-filament::section>
                <div class="space-y-2">
                    <div class="text-sm text-gray-500">
                        Hasil Simulasi - {{ $this->simulation['ratio'] }}
                    </div>
                    <div
                        @class([
                            'text-3xl font-semibold',
                            $colorClasses[$this->simulation['color']] ?? $colorClasses['gray'],
                        ])
                    >
                        {{ $this->simulation['formatted'] }}
                    </div>
                    @if ($this->simulation['status'])
                        <x-filament::badge color="{{ $this->simulation['color'] }}">
                            {{ $this->simulation['status'] }}
                        </x-filament::badge>
                    @endif
                    @if ($this->simulation['requirement'])
                        <div class="text-sm text-gray-500">
                            Ketentuan OJK: {{ $this->simulation['requirement'] }}
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
