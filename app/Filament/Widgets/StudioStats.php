<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Support\Studio;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudioStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $status = Studio::status();

        return [
            Stat::make('Order baru', Order::where('status', 'new')->count())
                ->description('Belum dibalas dengan quote')
                ->color('warning'),
            Stat::make('Slot terpakai', Studio::slotsTaken().' / '.Studio::slotsTotal())
                ->description(match ($status['state']) {
                    'open' => 'Commission buka',
                    'full' => 'Slot penuh, form order tertutup',
                    default => 'Commission ditutup manual',
                })
                ->color($status['state'] === 'open' ? 'success' : 'danger'),
            Stat::make('Selesai bulan ini', Order::where('status', 'completed')
                ->where('completed_at', '>=', now()->startOfMonth())
                ->count())
                ->color('primary'),
        ];
    }
}
