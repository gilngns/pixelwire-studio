<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Setting;
use App\Support\Markdown;
use App\Support\Studio;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email ke klien saat admin mengubah status order. Dikirim dalam bahasa
 * yang dipakai klien saat order (lihat OrderNotifier::statusChanged).
 */
class OrderStatusUpdated extends Notification
{
    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('Update on your order :code', ['code' => $this->order->code]))
            ->greeting(__('Hi :name,', ['name' => Markdown::escapeLinks($this->order->name)]))
            ->line(__('Your order status is now: :status.', ['status' => $this->order->statusLabel()]));

        foreach ($this->details() as $line) {
            $mail->line($line);
        }

        return $mail
            ->action(__('Check order status'), route('orders.track'))
            ->salutation(config('studio.name'));
    }

    /**
     * Penjelasan tambahan sesuai status, termasuk harga dan info pembayaran kalau relevan.
     */
    private function details(): array
    {
        $percent = Studio::depositPercent();

        return match ($this->order->status) {
            'quoted' => $this->order->quotedPriceLabel()
                ? [__('Your quote is :price. Reply to confirm, then pay the :percent% deposit to reserve your slot.', ['price' => $this->order->quotedPriceLabel(), 'percent' => $percent])]
                : [__('I have reviewed your brief. The quote will be sent through the contact you chose.')],
            'waiting_payment' => [
                __('Your slot is reserved once the :percent% deposit is paid.', ['percent' => $percent]),
                ...$this->paymentLines(),
            ],
            'in_progress' => [__('Payment received. I have started working on your widget.')],
            'revision' => [__('I am working on your revision request.')],
            'completed' => [__('Your widget is finished! The files and setup guide will be sent through the contact you chose.')],
            'cancelled' => [__('This order was cancelled. Reach out if you think this is a mistake.')],
            default => [],
        };
    }

    /**
     * Detail rekening (privat) kalau sudah diisi di admin; kalau belum, metode pembayaran publik.
     */
    private function paymentLines(): array
    {
        $details = trim((string) Setting::get('payment_details', ''));
        $info = $details !== '' ? $details : trim((string) Setting::get('payment_info', ''));

        if ($info === '') {
            return [];
        }

        $lines = [__('Payment details:'), ...preg_split('/\r?\n/', $info)];

        if ($details !== '') {
            $lines[] = __('Write your order code (:code) in the transfer note.', ['code' => $this->order->code]);
            $lines[] = __('You can also see these details on the order status page.');
        }

        return $lines;
    }
}
