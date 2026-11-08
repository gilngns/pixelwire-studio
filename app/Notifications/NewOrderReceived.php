<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\Markdown;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderReceived extends Notification
{
    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $o = $this->order;
        // Isian klien di-escape supaya tidak bisa menyisipkan link atau gambar markdown ke email admin.
        $text = fn (?string $value) => Markdown::escapeLinks($value);

        return (new MailMessage)
            ->subject("Order baru {$o->code} dari {$o->name}")
            ->greeting("Order baru masuk: {$o->code}")
            ->line('Layanan: '.($o->service?->name ?? 'Custom / belum memilih'))
            ->line('Platform: '.config('studio.platforms.'.$o->platform))
            ->line('Kontak: '.config('studio.contact_platforms.'.$o->contact_platform).' '.$text($o->contact_handle).' / '.$text($o->email))
            ->line('Aset desain: '.$o->designLabel())
            ->line('Add-on: '.$o->addonsSummary())
            ->line('Estimasi total: '.($o->estimatedTotalLabel() ?? '-'))
            ->line('Budget: '.($o->budget ? $text($o->budget) : '-').' ('.$o->currency.')')
            ->line('Deadline: '.($o->deadline?->format('d M Y') ?? '-'))
            ->line('Brief: '.$text($o->description))
            ->action('Buka di admin', url('/admin/orders/'.$o->id.'/edit'));
    }
}
