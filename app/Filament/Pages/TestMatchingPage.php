<?php



namespace App\Filament\Pages;

use App\Models\Startup;
use App\Services\OpportunityMatchingService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class TestMatchingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Tester le Matching';

    protected static string|\UnitEnum|null $navigationGroup = 'Matching';

    protected static ?string $navigationLabel = 'Tester le Matching';

    protected string $view = 'filament.pages.test-matching-page';

    public function mount(): void
    {
        $startupId = request()->query('startup');
        $data = ['selectedStartupId' => $startupId];
        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('selectedStartupId')
                    ->label('Startup à tester')
                    ->options(Startup::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Sélectionner une startup')
                    ->live(),
            ]);
    }

    public function getMatches(): Collection
    {
        $id = $this->data['selectedStartupId'] ?? null;
        if (! $id) {
            return collect();
        }

        $startup = Startup::find($id);
        if (! $startup) {
            return collect();
        }

        return app(OpportunityMatchingService::class)->calculateMatches($startup);
    }
}
