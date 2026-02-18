<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\AI\OpportunityExtractor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Artisan;

class TestScrapingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Tester le Scraping';

    protected static string|\UnitEnum|null $navigationGroup = 'Matching';

    protected static ?string $navigationLabel = 'Tester le Scraping';

    protected string $view = 'filament.pages.test-scraping-page';

    public ?array $extractedData = null;

    public function mount(): void
    {
        $this->form->fill([
            'raw_content' => $this->getSampleContent(),
            'external_url' => '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Textarea::make('raw_content')
                    ->label('Contenu brut (HTML ou texte)')
                    ->placeholder('Collez ici le contenu d\'une page d\'opportunité...')
                    ->rows(10)
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('external_url')
                    ->label('URL source (optionnel)')
                    ->url()
                    ->placeholder('https://...')
                    ->columnSpanFull(),
            ]);
    }

    public function extract(): void
    {
        $content = $this->data['raw_content'] ?? '';
        if (blank($content)) {
            $this->extractedData = null;

            return;
        }

        $extractor = app(OpportunityExtractor::class);
        $this->extractedData = $extractor->extractForTest($content);
    }

    public function runFullScrape(): void
    {
        if (! auth()->user()?->can('run_scraper')) {
            return;
        }

        Artisan::call('scrape:run');
        Notification::make()
            ->success()
            ->title('Scraping terminé')
            ->send();
    }

    protected function getSampleContent(): string
    {
        return <<<'HTML'
<h1>Tech Innovation Grant 2025</h1>
<p>Subvention pour startups tech. Montant: 50 000€ à 100 000€.</p>
<p>Date limite: 2025-06-15</p>
<p>Type: Grant - Ouvert aux secteurs Technology, HealthTech.</p>
<p>Stade: Seed, Series A</p>
HTML;
    }
}
