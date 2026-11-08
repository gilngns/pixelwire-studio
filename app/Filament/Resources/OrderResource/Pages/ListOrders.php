<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        return [
            'aktif' => Tab::make('Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotIn('status', ['completed', 'cancelled']))
                ->badge(Order::whereNotIn('status', ['completed', 'cancelled'])->count()),
            'baru' => Tab::make('Baru')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'new')),
            'selesai' => Tab::make('Selesai')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
            'semua' => Tab::make('Semua'),
        ];
    }
}
