<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Item yang bisa dipesan: paket satuan (package) atau bundle.
 */
class Service extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'category', 'summary', 'description', 'features',
        'price_idr', 'price_usd', 'compare_price_idr', 'compare_price_usd', 'price_is_starting',
        'turnaround', 'revisions', 'is_featured', 'is_active', 'sort_order',
    ];

    protected $attributes = [
        'type' => 'package',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'price_idr' => 'integer',
            'price_usd' => 'decimal:2',
            'compare_price_idr' => 'integer',
            'compare_price_usd' => 'decimal:2',
            'price_is_starting' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Service $service) {
            if (blank($service->slug)) {
                $service->slug = Str::slug($service->name);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePackages(Builder $query): Builder
    {
        return $query->where('type', 'package');
    }

    public function scopeBundles(Builder $query): Builder
    {
        return $query->where('type', 'bundle');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isBundle(): bool
    {
        return $this->type === 'bundle';
    }

    public function amountFor(string $currency): float
    {
        return $currency === 'USD' ? (float) $this->price_usd : (float) $this->price_idr;
    }

    public function priceFor(string $currency): string
    {
        return Money::format($this->amountFor($currency), $currency);
    }

    /**
     * Harga normal bundle (dicoret), atau null kalau tidak ada atau tidak lebih mahal.
     */
    public function comparePriceFor(string $currency): ?string
    {
        $compare = (float) ($currency === 'USD' ? $this->compare_price_usd : $this->compare_price_idr);

        return $compare > $this->amountFor($currency) ? Money::format($compare, $currency) : null;
    }

    public function savingsFor(string $currency): ?string
    {
        $compare = (float) ($currency === 'USD' ? $this->compare_price_usd : $this->compare_price_idr);
        $saving = $compare - $this->amountFor($currency);

        return $saving > 0 ? Money::format($saving, $currency) : null;
    }

    public function categoryLabel(): string
    {
        if ($this->isBundle()) {
            return __('Bundle');
        }

        return __(config('studio.categories.'.$this->category, (string) $this->category));
    }
}
