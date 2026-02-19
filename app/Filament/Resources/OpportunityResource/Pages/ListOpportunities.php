<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use App\Http\Controllers\Api\ScrapingController;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ListOpportunities extends ListRecords
{
    protected static string $resource = OpportunityResource::class;

    protected function getTableHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('recuperer')
                ->label('Fetch opportunities')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->modalHeading('Fetch opportunities')
                ->modalDescription('Describe the opportunities you are looking for. Gemini finds URLs and scraping extracts the data.')
                ->modalSubmitActionLabel('Fetch')
                ->form([
                    Textarea::make('query')
                        ->label('Search criteria')
                        ->placeholder('e.g. tech opportunities November $200,000 Senegal')
                        ->rows(3)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $request = Request::create('/api/scraping/fetch', 'POST', [
                        'query' => trim($data['query'] ?? ''),
                    ]);
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
                            ->body($body['message'] ?? 'No opportunities found for this query.')
                            ->send();

                        return;
                    }

                    session()->flash('extracted_opportunities', $opportunities);
                    session()->flash('extracted_notification', true);

                    $this->redirect(OpportunityResource::getUrl('create'));
                }),
        ];
    }
}
