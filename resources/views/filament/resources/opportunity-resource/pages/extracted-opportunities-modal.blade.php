@php
    $validFundingTypes = ['grant', 'equity', 'debt', 'prize', 'other'];
    $badgeColor = fn ($type) => match (strtolower(trim($type ?? ''))) {
        'grant' => 'success',
        'equity' => 'primary',
        'debt' => 'warning',
        'prize' => 'info',
        'other' => 'gray',
        default => 'gray',
    };
    $cleanText = fn (?string $s) => $s ? e(html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8')) : null;
    $displayFundingType = fn ($type) => in_array(strtolower(trim($type ?? '')), $validFundingTypes) ? ucfirst(strtolower(trim($type))) : null;
@endphp

<x-filament::modal
    id="extracted-opportunities-modal"
    heading="Retrieved opportunities"
    :description="count($opportunities) . ' opportunity(ies) found. Validate to save to database.'"
    width="4xl"
    :close-button="true"
>
    <div class="space-y-4 max-h-[70vh] overflow-y-auto">
        @foreach($opportunities as $index => $opp)
            <x-filament::section
                :heading="$cleanText($opp['title'] ?? null) ?? 'Untitled'"
                class="fi-section"
            >
                <dl class="fi-section-content-ctn grid gap-3 sm:grid-cols-2">
                    @if(!empty($opp['description']))
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $cleanText($opp['description']) }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Funding type</dt>
                        <dd class="mt-1">
                            @if(($ft = $displayFundingType($cleanText($opp['funding_type'] ?? null))))
                                <x-filament::badge :color="$badgeColor($ft)">
                                    {{ $ft }}
                                </x-filament::badge>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>
                    @if(!empty($opp['deadline']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Deadline</dt>
                        <dd class="mt-1 text-sm">{{ \Carbon\Carbon::parse($opp['deadline'])->format('d/m/Y') }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Min amount</dt>
                        <dd class="mt-1 text-sm">
                            @if(isset($opp['funding_min']) && $opp['funding_min'] !== null)
                                {{ number_format((float) $opp['funding_min'], 0, ',', ' ') }} $
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Max amount</dt>
                        <dd class="mt-1 text-sm">
                            @if(isset($opp['funding_max']) && $opp['funding_max'] !== null)
                                {{ number_format((float) $opp['funding_max'], 0, ',', ' ') }} $
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>
                    @if(!empty($opp['external_url']))
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Source / URL</dt>
                            <dd class="mt-1">
                                <a href="{{ $opp['external_url'] }}" target="_blank" rel="noopener" class="text-primary-600 hover:underline text-sm break-all">
                                    {{ $opp['external_url'] }}
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>

                <div class="fi-section-footer-ctn mt-4 flex flex-wrap gap-2">
                    <x-filament::button
                        color="success"
                        size="sm"
                        icon="heroicon-o-check"
                        wire:click="validerOpportunite({{ $index }})"
                        wire:loading.attr="disabled"
                    >
                        Validate and save
                    </x-filament::button>
                    <x-filament::button
                        color="gray"
                        size="sm"
                        icon="heroicon-o-pencil-square"
                        wire:click="remplirFormulaire({{ $index }})"
                    >
                        Edit before saving
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <x-slot name="footer">
        <div class="flex flex-wrap gap-2 justify-end">
            @if(count($opportunities) > 1)
                <x-filament::button
                    color="success"
                    icon="heroicon-o-check-circle"
                    wire:click="enregistrerToutes"
                    wire:loading.attr="disabled"
                >
                    Save all ({{ count($opportunities) }})
                </x-filament::button>
            @endif
            <x-filament::button
                color="gray"
                x-on:click="$dispatch('close-modal', { id: 'extracted-opportunities-modal' })"
            >
                Close
            </x-filament::button>
        </div>
    </x-slot>
</x-filament::modal>
