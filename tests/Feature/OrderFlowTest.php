<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Notifications\NewOrderReceived;
use App\Notifications\OrderConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_is_saved_with_code_and_notifications_are_sent(): void
    {
        Notification::fake();
        Http::fake();
        config(['studio.discord_webhook' => 'https://discord.test/webhook']);
        $service = $this->makeService();

        $response = $this->post('/order', $this->orderPayload(['service_id' => $service->id]));

        $order = Order::sole();
        $this->assertMatchesRegularExpression('/^PW-\d{4}-[A-Z0-9]{6}$/', $order->code);
        $this->assertStringStartsWith('PW-'.now()->format('ym').'-', $order->code);
        $this->assertSame('new', $order->status);
        $this->assertSame($service->id, $order->service_id);
        $response->assertRedirect(route('orders.thanks', $order->code));

        $this->get(route('orders.thanks', $order->code))->assertOk()->assertSee($order->code);

        Notification::assertSentOnDemand(NewOrderReceived::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === config('studio.admin_email'));
        Notification::assertSentOnDemand(OrderConfirmation::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'mochi@example.com');
        Http::assertSent(fn ($request) => $request->url() === 'https://discord.test/webhook'
            && str_contains(json_encode($request->data()), $order->code));
    }

    public function test_failing_email_and_discord_do_not_cancel_the_order(): void
    {
        config([
            'mail.default' => 'not-configured',
            'studio.discord_webhook' => 'https://discord.test/webhook',
        ]);
        Http::fake(['*' => Http::response('down', 500)]);

        $response = $this->post('/order', $this->orderPayload());

        $order = Order::sole();
        $response->assertRedirect(route('orders.thanks', $order->code));
    }

    public function test_honeypot_blocks_bots(): void
    {
        Notification::fake();

        $this->post('/order', $this->orderPayload(['website' => 'https://spam.example']))
            ->assertRedirect(route('home'));

        $this->assertSame(0, Order::count());
    }

    public function test_order_form_is_rate_limited(): void
    {
        Notification::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/order', $this->orderPayload(['email' => "mochi{$i}@example.com"]))->assertRedirect();
        }

        $this->post('/order', $this->orderPayload(['email' => 'lagi@example.com']))->assertStatus(429);
        $this->assertSame(5, Order::count());
    }

    public function test_validation_messages_are_in_indonesian(): void
    {
        $this->get('/order?lang=id');

        $this->post('/order', $this->orderPayload([
            'email' => 'bukan-email',
            'description' => 'pendek',
            'agree_terms' => null,
        ]));

        $errors = session('errors');
        $this->assertStringContainsString('alamat email yang valid', $errors->first('email'));
        $this->assertSame('Jelaskan widget-nya minimal 20 karakter.', $errors->first('description'));
        $this->assertSame('Baca dan setujui ketentuan sebelum mengirim order.', $errors->first('agree_terms'));
        $this->assertSame(0, Order::count());
    }

    public function test_client_links_must_be_http_or_https(): void
    {
        $this->post('/order', $this->orderPayload([
            'channel_url' => 'ftp://files.example.com/x',
            'design_link' => 'javascript:alert(1)',
        ]))->assertSessionHasErrors(['channel_url', 'design_link']);

        $this->assertSame(0, Order::count());
    }

    public function test_form_locks_when_all_slots_are_taken(): void
    {
        Notification::fake();
        Setting::put('slots_total', 2);
        $this->makeOrder('waiting_payment');
        $this->makeOrder('in_progress');

        $this->get('/order')
            ->assertOk()
            ->assertSee('All slots taken')
            ->assertSee('New orders are paused');

        $this->post('/order', $this->orderPayload())->assertSessionHas('error');
        $this->assertSame(2, Order::count());
    }

    public function test_only_active_statuses_use_a_slot(): void
    {
        Setting::put('slots_total', 2);
        $this->makeOrder('revision');
        $this->makeOrder('new');
        $this->makeOrder('quoted');
        $this->makeOrder('completed');
        $this->makeOrder('cancelled');

        $this->get('/order')->assertSee('Open, 1 slot left')->assertDontSee('New orders are paused');
    }

    public function test_form_locks_when_commissions_are_closed(): void
    {
        Notification::fake();
        Setting::put('commission_open', false);

        $this->get('/order')->assertSee('Commissions closed')->assertSee('New orders are paused');

        $this->post('/order', $this->orderPayload())->assertSessionHas('error');
        $this->assertSame(0, Order::count());
    }

    public function test_client_can_track_order_with_code_and_email(): void
    {
        $order = $this->makeOrder('quoted', ['quoted_price' => 450000, 'quoted_currency' => 'IDR']);

        $this->post('/track', ['code' => strtolower($order->code), 'email' => 'mochi@example.com'])
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee('Quote sent')
            ->assertSee('Rp450.000');
    }

    public function test_tracking_with_wrong_email_shows_error(): void
    {
        $order = $this->makeOrder();

        $this->from('/track')
            ->post('/track', ['code' => $order->code, 'email' => 'someone@example.com'])
            ->assertRedirect('/track')
            ->assertSessionHas('error');
    }

    public function test_design_service_is_not_offered(): void
    {
        Notification::fake();

        $this->get('/order')
            ->assertSee('I do not make designs.')
            ->assertSee('My design assets are ready')
            ->assertDontSee('I need help with the design too');

        $this->post('/order', $this->orderPayload(['has_design' => 'no']))->assertSessionHasErrors('has_design');
        $this->post('/order', $this->orderPayload(['has_design' => 'partial']))->assertSessionHasNoErrors();

        $this->assertSame('Some assets are still being made, I will send them before work starts', Order::sole()->designLabel());
        $this->assertSame('Asked for design help (old option)', $this->makeOrder('new', ['has_design' => 'no'])->designLabel());
    }
}
