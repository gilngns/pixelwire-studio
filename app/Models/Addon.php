<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Tambahan yang bisa dipilih bersama paket atau bundle.
 * Jenis harga: fixed (harga tetap), percent (persen dari harga paket), free (gratis).
 */
class Addon extends Model
{
    public const PRICING_TYPES = [
        'fixed' => 'Harga tetap',
        'percent' => 'Persentase dari harga paket',
        'free' => 'Gratis',
    ];

    protected $fillable = [
        'name', 'slug', 'description', 'pricing_type', 'price_idr', 'price_usd',
        'percent', 'max_quantity', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_idr' => 'integer',
            'price_usd' => 'decimal:2',
            'percent' => 'integer',
            'max_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Addon $addon) {
            if (blank($addon->slug)) {
                $addon->slug = Str::slug($addon->name);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Harga satu unit add-on. Add-on persen dihitung dari harga paket, lalu dibulatkan
     * ke Rp1.000 atau $1 terdekat. Null kalau add-on persen dipilih tanpa paket.
     */
    public function unitAmount(string $currency, ?float $packageAmount): ?float
    {
        return match ($this->pricing_type) {
            'free' => 0.0,
            'percent' => $packageAmount === null ? null : static::roundFor($packageAmount * $this->percent / 100, $currency),
            default => (float) ($currency === 'USD' ? $this->price_usd : $this->price_idr),
        };
    }

    public static function roundFor(float $amount, string $currency): float
    {
        return $currency === 'USD' ? round($amount) : round($amount / 1000) * 1000;
    }

    /**
     * Label harga di website, misalnya "+Rp120.000", "+40% of the package price" atau "Free".
     */
    public function priceLabel(string $currency): string
    {
        return match ($this->pricing_type) {
            'free' => __('Free'),
            'percent' => '+'.__(':percent% of the package price', ['percent' => $this->percent]),
            default => '+'.Money::format((float) ($currency === 'USD' ? $this->price_usd : $this->price_idr), $currency),
        };
    }
}
