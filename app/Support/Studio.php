<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Setting;

class Studio
{
    public static function slotsTotal(): int
    {
        return max(0, (int) Setting::get('slots_total', 5));
    }

    public static function slotsTaken(): int
    {
        return once(fn () => Order::usingSlot()->count());
    }

    public static function slotsLeft(): int
    {
        return max(0, static::slotsTotal() - static::slotsTaken());
    }

    /**
     * Commission dianggap buka kalau toggle aktif dan masih ada slot kosong.
     */
    public static function isOpen(): bool
    {
        return (bool) Setting::get('commission_open', true) && static::slotsLeft() > 0;
    }

    public static function depositPercent(): int
    {
        return (int) Setting::get('deposit_percent', 50);
    }

    /**
     * Estimasi waktu balas brief, misalnya "1 to 3 days" atau "1 sampai 3 hari".
     */
    public static function replyTime(): string
    {
        $min = (int) Setting::get('reply_days_min', 1);
        $max = max($min, (int) Setting::get('reply_days_max', 3));

        return $min === $max
            ? trans_choice(':count day|:count days', $max)
            : __(':min to :max days', ['min' => $min, 'max' => $max]);
    }

    /**
     * Status singkat untuk badge di header dan halaman order.
     */
    public static function status(): array
    {
        $total = static::slotsTotal();
        $left = static::slotsLeft();
        $toggle = (bool) Setting::get('commission_open', true);

        if (! $toggle) {
            $state = 'closed';
            $label = __('Commissions closed');
        } elseif ($left === 0) {
            $state = 'full';
            $label = __('All slots taken');
        } else {
            $state = 'open';
            $label = trans_choice('Open, :count slot left|Open, :count slots left', $left, ['count' => $left]);
        }

        return compact('state', 'label', 'total', 'left');
    }
}
