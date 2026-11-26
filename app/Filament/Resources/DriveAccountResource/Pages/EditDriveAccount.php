<?php

namespace App\Filament\Resources\DriveAccountResource\Pages;

use App\Filament\Resources\DriveAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDriveAccount extends EditRecord
{
    protected static string $resource = DriveAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
