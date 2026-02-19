<?php



namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class RegistrationsChartWidget extends ChartWidget
{
    protected ?string $heading = 'Inscriptions (7 derniers jours)';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = User::whereDate('created_at', $date)->count();
            $data['labels'][] = $date->format('d/m');
            $data['datasets'][0]['data'][] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Inscriptions',
                    'data' => $data['datasets'][0]['data'] ?? [],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => $data['labels'] ?? [],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
