<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\LineChartWidget;
use Illuminate\Support\Carbon;

class UserRegistrationsChart extends LineChartWidget
{
    protected static ?string $heading = 'User Registrations';

    protected function getData(): array
    {
        $data = User::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Registrations',
                    'data' => $data->pluck('count'),
                ],
            ],
            'labels' => $data->pluck('date')->map(fn ($date) => Carbon::parse($date)->format('M d')),
        ];
    }
}
