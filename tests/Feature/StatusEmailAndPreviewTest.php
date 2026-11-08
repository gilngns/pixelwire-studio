<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class StatusEmailAndPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret-password']));
    }

    private function mailLines(Order $order, string $locale): array
    {
        app()->setLocale($locale);
        $mail = (new OrderStatusUpdated($order))->toMail(new AnonymousNotifiable);

        return [$mail->subject, ...$mail->introLines];
    }

    public function test_order_remembers_the_language_used_to_order(): void
    {
        Notification::fake();
        $this->get('/order?lang=id');

        $this->post('/order', $this->orderPayload());

        $this->assertSame('id', Order::sole()->locale);
    }

    public function test_changing_status_in_admin_emails_the_client_in_their_language(): void
    {
        Notification::fake();
        $this->actingAsAdmin();
        $order = $this->makeOrder('new', ['locale' => 'id']);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->assertFormSet(['notify_client' => true])
            ->fillForm(['status' => 'quoted', 'quoted_price' => 450000, 'quoted_currency' => 'IDR'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Email update dikirim ke mochi@example.com');

        Notification::assertSentOnDemand(OrderStatusUpdated::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'mochi@example.com' && $notification->locale === 'id');
    }

    public function test_no_email_when_toggle_is_off_or_status_is_unchanged(): void
    {
        Notification::fake();
        $this->actingAsAdmin();
        $order = $this->makeOrder('new');

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(['status' => 'quoted', 'notify_client' => false])
            ->call('save');

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(['admin_notes' => 'Catatan saja'])
            ->call('save');

        Notification::assertNothingSent();
        $this->assertSame('quoted', $order->fresh()->status);
    }

    public function test_status_email_is_really_sent_with_the_right_subject(): void
    {
        $this->actingAsAdmin();
        $order = $this->makeOrder('quoted', ['locale' => 'id']);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(['status' => 'waiting_payment'])
            ->call('save');

        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $email = $messages[0]->getOriginalMessage();
        $this->assertSame('Kabar terbaru order '.$order->code, $email->getSubject());
        $this->assertSame('mochi@example.com', $email->getTo()[0]->getAddress());
        $this->assertStringContainsString('Menunggu pembayaran', $email->getHtmlBody());
    }

    public function test_status_email_content_per_status(): void
    {
        $order = $this->makeOrder('quoted', ['quoted_price' => 450000, 'quoted_currency' => 'IDR']);

        $this->assertSame([
            'Kabar terbaru order '.$order->code,
            'Status order kamu sekarang: Penawaran dikirim.',
            'Penawaran untuk order kamu: Rp450.000. Balas untuk konfirmasi, lalu bayar DP 50% untuk mengamankan slot.',
        ], $this->mailLines($order, 'id'));

        $order->status = 'waiting_payment';
        $lines = $this->mailLines($order, 'en');
        $this->assertContains('Your slot is reserved once the 50% deposit is paid.', $lines);
        $this->assertContains('Payment details:', $lines);
        $this->assertContains('Indonesia: bank transfer or e-wallet (IDR).', $lines);

        $order->status = 'completed';
        $this->assertContains('Your widget is finished! The files and setup guide will be sent through the contact you chose.', $this->mailLines($order, 'en'));
    }

    public function test_pages_have_link_preview_tags(): void
    {
        $this->get('/services')
            ->assertSee('<meta property="og:title" content="Services &amp; prices | Pixelwire Studio">', false)
            ->assertSee('<meta property="og:image" content="'.asset('og-image.png').'">', false)
            ->assertSee('<meta property="og:image:width" content="1200">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<meta name="twitter:site" content="@pixelwirestudio">', false);

        $size = getimagesize(public_path('og-image.png'));
        $this->assertSame([1200, 630], [$size[0], $size[1]]);
    }
}
