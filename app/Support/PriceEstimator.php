<?php

namespace App\Support;

use App\Models\Addon;
use App\Models\Service;

/**
 * Estimasi total order: harga paket atau bundle ditambah add-on yang dipilih.
 * Dipakai saat order disimpan, jadi angka dari browser tidak pernah dipercaya.
 */
class PriceEstimator
{
    /**
     * @param  array<int|string, int>  $selections  id add-on => jumlah
     * @return array{total: ?float, is_starting: bool, addons: list<array<string, mixed>>}
     */
    public static function estimate(?Service $service, array $selections, string $currency): array
    {
        $base = $service?->amountFor($currency);

        $addons = Addon::active()
            ->whereIn('id', array_keys($selections))
            ->get()
            ->map(function (Addon $addon) use ($selections, $currency, $base) {
                $quantity = max(1, min((int) $selections[$addon->id], $addon->max_quantity));
                $unit = $addon->unitAmount($currency, $base);

                return [
                    'id' => $addon->id,
                    'name' => $addon->name,
                    'pricing_type' => $addon->pricing_type,
                    'percent' => $addon->percent,
                    'quantity' => $quantity,
                    'amount' => $unit === null ? null : $unit * $quantity,
                ];
            })
            ->values()
            ->all();

        return [
            'total' => $base === null ? null : $base + array_sum(array_column($addons, 'amount')),
            'is_starting' => (bool) $service?->price_is_starting,
            'addons' => $addons,
        ];
    }
}
