<x-filament-panels::page>
    <div>
        {{ $this->form }}

        @if($this->getMatches()->isNotEmpty())
            <div class="mt-6">
                <h3 class="text-lg font-semibold mb-4">Résultats du matching</h3>
                <x-filament::section>
                    <div class="overflow-x-auto">
                        <table class="fi-ta-table w-full text-start">
                            <thead>
                                <tr class="fi-ta-header-row border-b border-gray-200 dark:border-white/5">
                                    <th class="fi-ta-header-cell px-3 py-3 text-start">Opportunité</th>
                                    <th class="fi-ta-header-cell px-3 py-3 text-start">Score</th>
                                    <th class="fi-ta-header-cell px-3 py-3 text-start">Breakdown</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->getMatches() as $matchItem)
                                    <tr class="fi-ta-row border-b border-gray-200 dark:border-white/5">
                                        <td class="fi-ta-cell px-3 py-3">
                                            @php($opp = $matchItem['opportunity'] ?? null)
                                            <strong>{{ $opp?->title ?? ($opp['title'] ?? '-') }}</strong>
                                            @if($opp)
                                                <br><span class="text-sm text-gray-500">{{ $opp->funding_type ?? ($opp['funding_type'] ?? '') }} • {{ is_object($opp->deadline ?? null) ? $opp->deadline->format('d/m/Y') : ($opp['deadline'] ?? '-') }}</span>
                                            @endif
                                        </td>
                                        <td class="fi-ta-cell px-3 py-3">
                                            @php($score = (int) round($matchItem['score'] ?? 0))
                                            <x-filament::badge :color="$score >= 70 ? 'success' : ($score >= 40 ? 'warning' : 'gray')">
                                                {{ $score }}%
                                            </x-filament::badge>
                                        </td>
                                        <td class="fi-ta-cell px-3 py-3 text-sm">
                                            @foreach((array)($matchItem['breakdown'] ?? []) as $key => $val)
                                                <span class="inline-block mr-2">{{ $key }}: {{ round($val) }}</span>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>
        @elseif(filled($this->data['selectedStartupId'] ?? null))
            <div class="mt-6">
                <x-filament::section>
                    <p class="text-gray-500 dark:text-gray-400">Aucune opportunité matchée pour cette startup.</p>
                </x-filament::section>
            </div>
        @endif
    </div>
</x-filament-panels::page>
