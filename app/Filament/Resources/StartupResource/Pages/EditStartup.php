<?php

declare(strict_types=1);

namespace App\Filament\Resources\StartupResource\Pages;

use App\Filament\Resources\StartupResource;
use Filament\Resources\Pages\EditRecord;

class EditStartup extends EditRecord
{
    protected static string $resource = StartupResource::class;
}
