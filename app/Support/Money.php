<?php

namespace App\Support;

class Money
{
    public static function format(float $amount, string $currency): string
    {
        return $currency === 'USD'
            ? '$'.(fmod($amount, 1.0) === 0.0 ? number_format($amount, 0) : number_format($amount, 2))
            : 'Rp'.number_format($amount, 0, ',', '.');
    }
}
