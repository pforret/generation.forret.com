<?php

namespace App\Filament\Resources\GenerationResource\Pages;

use App\Filament\Resources\GenerationResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGeneration extends EditRecord
{
    protected static string $resource = GenerationResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
