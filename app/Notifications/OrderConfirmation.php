<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\Markdown;
use App\Support\Studio;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmation extends Notification
{
    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('We received your order :code', ['code' => $this->order->code]))
            ->greeting(__('Hi :name,', ['name' => Markdown::escapeLinks($this->order->name)]))
            ->line(__('Your commission request has been received. I will review the brief and reply with a quote within :time.', ['time' => Studio::replyTime()]))
            ->line(__('Your order code is :code. Keep it to check the progress of your order.', ['code' => $this->order->code]))
            ->action(__('Check order status'), route('orders.track'))
            ->salutation(config('studio.name'));
    }
}
