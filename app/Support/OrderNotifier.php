<?php

namespace App\Support;

use App\Models\Order;
use App\Notifications\NewOrderReceived;
use App\Notifications\OrderConfirmation;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class OrderNotifier
{
    public function newOrder(Order $order): void
    {
        $this->safely(fn () => Notification::route('mail', config('studio.admin_email'))
            ->notify(new NewOrderReceived($order)));

        $this->safely(fn () => Notification::route('mail', $order->email)
            ->notify(new OrderConfirmation($order)));

        $this->safely(fn () => $this->discord($order));
    }

    /**
     * Kabari klien saat status order diubah dari admin, dalam bahasa yang dipakai saat order.
     */
    public function statusChanged(Order $order): void
    {
        $this->safely(fn () => Notification::route('mail', $order->email)
            ->notify((new OrderStatusUpdated($order))->locale($order->locale ?: 'en')));
    }

    private function discord(Order $order): void
    {
        $webhook = config('studio.discord_webhook');

        if (blank($webhook)) {
            return;
        }

        // Discord juga merender markdown: isian klien di-escape, dan mention (@everyone) dimatikan.
        $text = fn (?string $value) => Markdown::escapeLinks($value);

        Http::timeout(5)->post($webhook, [
            'username' => config('studio.name'),
            'allowed_mentions' => ['parse' => []],
            'embeds' => [[
                'title' => "Order baru {$order->code}",
                'url' => url('/admin/orders/'.$order->id.'/edit'),
                'color' => 0xFF7AB2,
                'fields' => [
                    ['name' => 'Nama', 'value' => $text($order->name), 'inline' => true],
                    ['name' => 'Layanan', 'value' => $order->service?->name ?? 'Custom', 'inline' => true],
                    ['name' => 'Platform', 'value' => config('studio.platforms.'.$order->platform), 'inline' => true],
                    ['name' => 'Kontak', 'value' => config('studio.contact_platforms.'.$order->contact_platform).': '.$text($order->contact_handle ?: $order->email)],
                    ['name' => 'Add-on', 'value' => str($order->addonsSummary())->limit(900)->toString()],
                    ['name' => 'Estimasi', 'value' => $order->estimatedTotalLabel() ?? '-', 'inline' => true],
                    ['name' => 'Budget', 'value' => ($order->budget ? $text($order->budget) : '-').' ('.$order->currency.')', 'inline' => true],
                    ['name' => 'Deadline', 'value' => $order->deadline?->format('d M Y') ?? '-', 'inline' => true],
                    ['name' => 'Brief', 'value' => $text(str($order->description)->limit(900)->toString())],
                ],
            ]],
        ])->throw();
    }

    /**
     * Gagal kirim notifikasi tidak boleh menggagalkan order yang sudah tersimpan.
     */
    private function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::warning('Order notification failed: '.$e->getMessage());
        }
    }
}
