<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Enums\OpportunityStatus;
use App\Filament\Resources\OpportunityResource;
use App\Http\Controllers\Api\ScrapingController;
use App\Models\Opportunity;
use App\services\Scraping\OpportunityDataMapper;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreateOpportunity extends CreateRecord
{
    protected static string $resource = OpportunityResource::class;

    /** @var array<int, array<string, mixed>> */
    public array $extractedOpportunities = [];

    public function getSubheading(): ?string
    {
        return 'Enter manually or use Gemini to fetch and validate opportunities.';
    }

    protected function fillForm(): void
    {
        parent::fillForm();

        $opportunities = session()->pull('extracted_opportunities');
        if (is_array($opportunities) && ! empty($opportunities)) {
            $this->extractedOpportunities = $opportunities;
            session()->put('extracted_opportunities', $opportunities);
            session()->put('extracted_opportunity_index', 0);
            if (session()->pull('extracted_notification')) {
                $count = count($opportunities);
                Notification::make()
                    ->success()
                    ->title('Data extracted')
                    ->body($count > 1
                        ? "{$count} opportunities found. Validate in the popup to save."
                        : 'Validate in the popup to save.')
                    ->send();
            }
            $this->dispatch('open-modal', id: 'extracted-opportunities-modal');
        }
    }

    public function getFooter(): ?View
    {
        if (empty($this->extractedOpportunities)) {
            return null;
        }

        return view('filament.resources.opportunity-resource.pages.extracted-opportunities-modal', [
            'opportunities' => $this->extractedOpportunities,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $schema = parent::form($schema);
        $current = $schema->getComponents(withActions: false);

        $saisieManuelle = Section::make('Manual entry')
            ->description('Fill in the fields below to create an opportunity manually.')
            ->schema(is_array($current) ? $current : []);

        $recuperationSection = Section::make('Fetch via Gemini')
            ->description('Describe the opportunities you are looking for. AI finds URLs, extracts data, cleans it with Gemini, then shows a popup: validate to save.')
            ->schema([
                Textarea::make('query')
                    ->label('Search criteria')
                    ->placeholder('e.g. opportunities tech Ghana, grants Senegal, hackathons 2025')
                    ->rows(3)
                    ->dehydrated(false)
                    ->columnSpanFull(),
            ])
            ->footerActions([
                Action::make('recuperer')
                    ->label('Fetch with Gemini')
                    ->icon('heroicon-o-sparkles')
                    ->action('recuperer')
                    ->color('primary'),
            ])
            ->collapsible()
            ->collapsed();

        return $schema->components([
            $saisieManuelle,
            $recuperationSection,
        ]);
    }

    public function recuperer(): void
    {
        $query = trim($this->data['query'] ?? '');
        if ($query === '') {
            Notification::make()
                ->warning()
                ->title('Missing query')
                ->body('Describe the opportunities you are looking for.')
                ->send();

            return;
        }

        Notification::make()
            ->info()
            ->title('Please wait')
            ->body('Gemini is searching and cleaning the data. This may take a minute.')
            ->send();

        $request = Request::create('/api/scraping/fetch', 'POST', ['query' => $query]);
        $request->setUserResolver(fn () => Auth::user());

        $response = app(ScrapingController::class)->fetch($request);

        if ($response->getStatusCode() >= 400) {
            $body = $response->getData(true) ?? [];
            Notification::make()
                ->danger()
                ->title('Error')
                ->body($body['message'] ?? 'Error during search.')
                ->send();

            return;
        }

        $body = $response->getData(true) ?? [];
        $opportunities = $body['opportunities'] ?? [];

        if (empty($opportunities)) {
            Notification::make()
                ->warning()
                ->title('No opportunities')
                ->body($body['message'] ?? 'No opportunities found.')
                ->send();

            return;
        }

        $this->extractedOpportunities = $opportunities;
        session()->put('extracted_opportunities', $opportunities);
        session()->put('extracted_opportunity_index', 0);

        $count = count($opportunities);
        Notification::make()
            ->success()
            ->title('Data extracted')
            ->body($count > 1 ? "{$count} opportunities found. Validate or save." : 'Validate or save the opportunity.')
            ->send();

        $this->dispatch('open-modal', id: 'extracted-opportunities-modal');
    }

    public function validerOpportunite(int $index): void
    {
        $opp = $this->extractedOpportunities[$index] ?? null;
        if (! $opp || empty($opp['title'])) {
            Notification::make()->warning()->title('Invalid data')->send();
            return;
        }

        if ($this->isDuplicate($opp)) {
            Notification::make()->warning()->title('Duplicate')->body('This opportunity already exists in the database.')->send();
            return;
        }

        $this->createOpportunityFromExtracted($opp);
        $this->removeExtractedAtIndex($index);
        Notification::make()->success()->title('Opportunity saved.')->send();
    }

    public function remplirFormulaire(int $index): void
    {
        $opp = $this->extractedOpportunities[$index] ?? null;
        if ($opp) {
            $this->form->partialRawState($opp);
            session()->put('extracted_opportunity_index', $index);
        }
        $this->dispatch('close-modal', id: 'extracted-opportunities-modal');
    }

    public function enregistrerToutes(): void
    {
        $saved = 0;
        $skipped = 0;
        foreach ($this->extractedOpportunities as $data) {
            if (empty($data['title'])) {
                continue;
            }
            if ($this->isDuplicate($data)) {
                $skipped++;
                continue;
            }
            $this->createOpportunityFromExtracted($data);
            $saved++;
        }
        $this->extractedOpportunities = [];
        session()->forget(['extracted_opportunities', 'extracted_opportunity_index']);
        $this->form->fill();
        $title = $saved > 0 ? "{$saved} opportunity(ies) saved." : 'No new opportunities';
        $body = $skipped > 0 ? "{$skipped} duplicate(s) skipped." : null;
        Notification::make()->success()->title($title)->body($body)->send();
        $this->dispatch('close-modal', id: 'extracted-opportunities-modal');
        $this->redirect(OpportunityResource::getUrl('index'));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $data
     */
    private function isDuplicate(array $data): bool
    {
        $title = trim($data['title'] ?? '');
        $url = $data['external_url'] ?? null;
        if ($title === '') {
            return false;
        }
        $query = Opportunity::withoutGlobalScopes()->where('title', $title);
        if ($url) {
            $query->where('external_url', $url);
        } else {
            $query->whereNull('external_url');
        }

        return $query->exists();
    }

    /**
     * Create opportunity from extracted data and sync industry/stage/country.
     *
     * @param  array<string, mixed>  $data
     */
    private function createOpportunityFromExtracted(array $data): void
    {
        $mapper = app(OpportunityDataMapper::class);
        $attributes = $mapper->toOpportunityAttributes($data);
        $attributes['status'] = OpportunityStatus::Pending;

        $opportunity = Opportunity::create($attributes);

        $industryIds = $mapper->resolveIndustryIds($data['industry'] ?? null);
        if (! empty($industryIds)) {
            $opportunity->industries()->sync($industryIds);
        }

        $stageIds = $mapper->resolveStageIds($data['stage'] ?? null);
        if (! empty($stageIds)) {
            $opportunity->stages()->sync($stageIds);
        }

        $countryCodes = $mapper->resolveCountryCodes($data['country'] ?? null);
        if (! empty($countryCodes)) {
            foreach ($countryCodes as $code) {
                $opportunity->countryCodes()->create(['country_code' => $code]);
            }
        }
    }

    private function removeExtractedAtIndex(int $index): void
    {
        $arr = $this->extractedOpportunities;
        array_splice($arr, $index, 1);
        $this->extractedOpportunities = array_values($arr);
        session()->put('extracted_opportunities', $this->extractedOpportunities);
        if (empty($this->extractedOpportunities)) {
            $this->dispatch('close-modal', id: 'extracted-opportunities-modal');
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['query']);

        return $data;
    }

    protected function getFormActions(): array
    {
        return parent::getFormActions();
    }
}

