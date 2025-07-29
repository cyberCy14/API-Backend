<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\UserRegistrationsChart;

class Dashboard extends BaseDashboard
{
    /**
     * Get the widgets that should be displayed on the dashboard.
     *
     * @return array
     */
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.dashboard';

    // protected function getHeaderWidgets(): array
    // {
    //     // return [
    //     //     \App\Filament\Widgets\LoyaltyOverview::class,
    //     // ];
    // }
}
