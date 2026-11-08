<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getTabs(): array
    {
        return [
            'paket' => Tab::make('Paket')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'package')),
            'bundle' => Tab::make('Bundle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'bundle')),
        ];
    }
}
