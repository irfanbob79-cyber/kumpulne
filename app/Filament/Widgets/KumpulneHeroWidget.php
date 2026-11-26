<?php

namespace App\Filament\Widgets;

use App\Models\DriveAccount;
use Filament\Widgets\Widget;

class KumpulneHeroWidget extends Widget
{
    protected static string $view = 'filament.widgets.kumpulne-hero-widget';

    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $activeDrive = DriveAccount::where('is_active', true)
            ->where('is_full', false)
            ->orderBy('priority', 'asc')
            ->first();

        return [
            'userName' => auth()->user()?->name ?? 'Admin',
            'isDriveConnected' => $activeDrive && !empty($activeDrive->refresh_token),
            'driveEmail' => $activeDrive?->google_email,
        ];
    }
}
