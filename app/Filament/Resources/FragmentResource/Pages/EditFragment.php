<?php

namespace App\Filament\Resources\FragmentResource\Pages;

use App\Filament\Resources\FragmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFragment extends EditRecord
{
    protected static string $resource = FragmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
