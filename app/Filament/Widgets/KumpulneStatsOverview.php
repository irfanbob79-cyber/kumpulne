<?php

namespace App\Filament\Widgets;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\DriveAccount;
use App\Models\Submission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KumpulneStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalSubmissions = Submission::count();
        $onTimeCount = Submission::where('is_late', false)->count();
        $lateCount = Submission::where('is_late', true)->count();

        $activeAssignments = Assignment::where('is_active', true)->count();
        $activeClassrooms = Classroom::where('is_active', true)->count();

        $activeDrive = DriveAccount::where('is_active', true)
            ->where('is_full', false)
            ->orderBy('priority', 'asc')
            ->first();

        $isDriveConnected = $activeDrive && !empty($activeDrive->refresh_token);

        if ($isDriveConnected) {
            $usedGb = round($activeDrive->storage_used_bytes / (1024 * 1024 * 1024), 2);
            $limitGb = $activeDrive->storage_limit_bytes > 0
                ? round($activeDrive->storage_limit_bytes / (1024 * 1024 * 1024), 2) . ' GB'
                : 'Unlimited';

            $driveValue = 'Terhubung';
            $driveDesc = "{$activeDrive->google_email} ({$usedGb} GB / {$limitGb})";
            $driveColor = 'success';
            $driveIcon = 'heroicon-m-cloud-arrow-up';
        } else {
            $driveValue = 'Mode Lokal';
            $driveDesc = 'Belum ada akun Google Drive yang terhubung OAuth';
            $driveColor = 'warning';
            $driveIcon = 'heroicon-m-exclamation-triangle';
        }

        return [
            Stat::make('Total Tugas Terkumpul', $totalSubmissions)
                ->description("{$onTimeCount} tepat waktu • {$lateCount} terlambat")
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('primary'),

            Stat::make('Folder Tugas Aktif', $activeAssignments)
                ->description("Tersedia untuk {$activeClassrooms} kelas aktif")
                ->descriptionIcon('heroicon-m-folder-open')
                ->color('info'),

            Stat::make('Penyimpanan Google Drive', $driveValue)
                ->description($driveDesc)
                ->descriptionIcon($driveIcon)
                ->color($driveColor)
                ->url(route('filament.admin.resources.drive-accounts.index')),
        ];
    }
}
