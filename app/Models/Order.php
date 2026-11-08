<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'code', 'service_id', 'name', 'email', 'contact_platform', 'contact_handle',
        'channel_url', 'currency', 'locale', 'platform', 'has_design', 'design_link', 'description',
        'budget', 'addons', 'estimated_total', 'estimate_is_starting', 'deadline', 'status',
        'quoted_price', 'quoted_currency', 'partner_discount_percent', 'admin_notes', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'addons' => 'array',
            'estimated_total' => 'decimal:2',
            'estimate_is_starting' => 'boolean',
            'quoted_price' => 'decimal:2',
            'partner_discount_percent' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->code ??= static::generateCode();
        });

        static::saving(function (Order $order) {
            if ($order->isDirty('status')) {
                $order->completed_at = $order->status === 'completed' ? now() : null;
            }
        });
    }

    public static function generateCode(): string
    {
        do {
            // 6 karakter acak (sekitar 2 miliar kemungkinan per bulan), supaya kode sulit ditebak.
            $code = 'PW-'.now()->format('ym').'-'.Str::upper(Str::random(6));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeUsingSlot(Builder $query): Builder
    {
        return $query->whereIn('status', config('studio.slot_statuses'));
    }

    /**
     * Detail rekening hanya ditampilkan ke klien yang order-nya sudah dikonfirmasi dan masih perlu membayar (DP atau pelunasan).
     */
    public function showsPaymentDetails(): bool
    {
        return in_array($this->status, ['waiting_payment', 'in_progress', 'revision'], true);
    }

    public function statusLabel(): string
    {
        return __(config('studio.order_statuses.'.$this->status, $this->status));
    }

    public function estimatedTotalLabel(): ?string
    {
        if ($this->estimated_total === null) {
            return null;
        }

        $label = \App\Support\Money::format((float) $this->estimated_total, $this->currency);

        return $this->estimate_is_starting ? __('from').' '.$label : $label;
    }

    /**
     * Label pilihan aset desain. Order lama bisa berisi opsi yang sudah dihapus ("no" = minta bantuan desain).
     */
    public function designLabel(): string
    {
        return config('studio.design_options.'.$this->has_design)
            ?? ($this->has_design === 'no' ? 'Asked for design help (old option)' : (string) $this->has_design);
    }

    /**
     * Daftar add-on dalam satu baris teks, untuk admin, email, dan Discord.
     */
    public function addonsSummary(): string
    {
        return collect($this->addons ?? [])
            ->map(function (array $addon) {
                $name = $addon['name'].(($addon['quantity'] ?? 1) > 1 ? ' x'.$addon['quantity'] : '');
                $amount = $addon['amount'] === null ? '?' : \App\Support\Money::format((float) $addon['amount'], $this->currency);

                return $name.' ('.$amount.')';
            })
            ->implode(', ') ?: '-';
    }

    public function quotedPriceLabel(): ?string
    {
        if ($this->quoted_price === null) {
            return null;
        }

        return \App\Support\Money::format((float) $this->quoted_price, $this->quoted_currency ?? $this->currency);
    }
}
