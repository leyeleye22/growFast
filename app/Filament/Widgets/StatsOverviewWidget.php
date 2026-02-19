<?php



namespace App\Filament\Widgets;

use App\Models\Opportunity;
use App\Models\OpportunityMatch;
use App\Models\Startup;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = now()->startOfDay();

        return [
            Stat::make('Registrations today', User::where('created_at', '>=', $today)->count())
                ->description('New users')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Startups total', Startup::count())
                ->description('On platform')
                ->descriptionIcon('heroicon-m-rocket-launch'),

            Stat::make('Active opportunities', Opportunity::withoutGlobalScopes()->where('status', 'active')->count())
                ->description('Available')
                ->descriptionIcon('heroicon-m-banknotes'),

            Stat::make('Matches calculés', OpportunityMatch::count())
                ->description('Startup-Opportunity')
                ->descriptionIcon('heroicon-m-chart-bar'),
        ];
    }
}
