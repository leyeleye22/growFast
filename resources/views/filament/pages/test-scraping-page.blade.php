<x-filament-panels::page>
    <form wire:submit="extract">
        {{ $this->form }}

        <div class="mt-6 flex flex-wrap gap-2">
            <x-filament::button type="submit" color="primary">
                Extraire les données
            </x-filament::button>
            @if(auth()->user()?->can('run_scraper'))
                <x-filament::button
                    color="gray"
                    wire:click="runFullScrape"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="runFullScrape">Lancer le scraping complet</span>
                    <span wire:loading wire:target="runFullScrape">En cours...</span>
                </x-filament::button>
            @endif
        </div>

        @if($extractedData !== null)
            <div class="mt-6">
                <h3 class="text-lg font-semibold mb-4">Résultats de l'extraction</h3>
                <x-filament::section>
                    <div class="overflow-x-auto">
                        <table class="fi-ta-table w-full text-start">
                            <thead>
                                <tr class="fi-ta-header-row border-b border-gray-200 dark:border-white/5">
                                    <th class="fi-ta-header-cell px-3 py-3 text-start">Champ</th>
                                    <th class="fi-ta-header-cell px-3 py-3 text-start">Valeur</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="fi-ta-row border-b border-gray-200 dark:border-white/5">
                                    <td class="fi-ta-cell px-3 py-3 font-medium">Titre</td>
                                    <td class="fi-ta-cell px-3 py-3">{{ $extractedData['title'] ?? '-' }}</td>
                                </tr>
                                <tr class="fi-ta-row border-b border-gray-200 dark:border-white/5">
                                    <td class="fi-ta-cell px-3 py-3 font-medium">Type de financement</td>
                                    <td class="fi-ta-cell px-3 py-3">{{ $extractedData['funding_type'] ?? '-' }}</td>
                                </tr>
                                <tr class="fi-ta-row border-b border-gray-200 dark:border-white/5">
                                    <td class="fi-ta-cell px-3 py-3 font-medium">Date limite</td>
                                    <td class="fi-ta-cell px-3 py-3">
                                        @if(!empty($extractedData['deadline']))
                                            {{ \Carbon\Carbon::parse($extractedData['deadline'])->format('d/m/Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr class="fi-ta-row border-b border-gray-200 dark:border-white/5">
                                    <td class="fi-ta-cell px-3 py-3 font-medium">Montant min</td>
                                    <td class="fi-ta-cell px-3 py-3">
                                        @if(isset($extractedData['funding_min']) && $extractedData['funding_min'] !== null)
                                            {{ number_format((float) $extractedData['funding_min'], 0, ',', ' ') }} €
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr class="fi-ta-row border-b border-gray-200 dark:border-white/5">
                                    <td class="fi-ta-cell px-3 py-3 font-medium">Montant max</td>
                                    <td class="fi-ta-cell px-3 py-3">
                                        @if(isset($extractedData['funding_max']) && $extractedData['funding_max'] !== null)
                                            {{ number_format((float) $extractedData['funding_max'], 0, ',', ' ') }} €
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr class="fi-ta-row border-b border-gray-200 dark:border-white/5">
                                    <td class="fi-ta-cell px-3 py-3 font-medium">Industrie</td>
                                    <td class="fi-ta-cell px-3 py-3">{{ $extractedData['industry'] ?? '-' }}</td>
                                </tr>
                                <tr class="fi-ta-row border-b border-gray-200 dark:border-white/5">
                                    <td class="fi-ta-cell px-3 py-3 font-medium">Stade</td>
                                    <td class="fi-ta-cell px-3 py-3">{{ $extractedData['stage'] ?? '-' }}</td>
                                </tr>
                                @if(isset($extractedData['source']))
                                    <tr class="fi-ta-row border-b border-gray-200 dark:border-white/5">
                                        <td class="fi-ta-cell px-3 py-3 font-medium">Source extraction</td>
                                        <td class="fi-ta-cell px-3 py-3">
                                            <x-filament::badge :color="$extractedData['source'] === 'gemini' ? 'success' : 'gray'">
                                                {{ $extractedData['source'] === 'gemini' ? 'Gemini AI' : 'Regex' }}
                                            </x-filament::badge>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>
        @endif
    </form>
</x-filament-panels::page>
