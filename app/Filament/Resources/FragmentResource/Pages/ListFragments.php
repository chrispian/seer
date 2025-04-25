<?php

namespace App\Filament\Resources\FragmentResource\Pages;

use App\Filament\Resources\FragmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFragments extends ListRecords
{
    protected static string $resource = FragmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
