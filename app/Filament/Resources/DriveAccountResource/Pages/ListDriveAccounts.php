<?php

namespace App\Filament\Resources\DriveAccountResource\Pages;

use App\Filament\Resources\DriveAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDriveAccounts extends ListRecords
{
    protected static string $resource = DriveAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
