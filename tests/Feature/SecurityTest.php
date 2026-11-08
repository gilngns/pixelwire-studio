<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Models\Order;
use App\Models\PortfolioItem;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NewOrderReceived;
use App\Notifications\OrderConfirmation;
use App\Support\OrderNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private const XSS = '"><img src=x onerror=alert(1)><script>alert(2)</script>';

    public function test_markdown_links_from_clients_do_not_become_links_in_emails(): void
    {
        Notification::fake();

        $this->post('/order', $this->orderPayload([
            'name' => '[Bayar di sini](https://evil.example)',
            'description' => 'Lihat ![](https://evil.example/pixel.png) dan [ini](https://evil.example) ya.',
        ]));
        $order = Order::firstOrFail();

        foreach ([new OrderConfirmation($order), new NewOrderReceived($order)] as $notification) {
            $html = (string) $notification->toMail($order)->render();

            $this->assertStringNotContainsString('href="https://evil.example"', $html);
            $this->assertStringNotContainsString('<img src="https://evil.example', $html);
        }

        // Teksnya tetap terbaca apa adanya, hanya tidak menjadi link.
        $this->assertStringContainsString('Hi [Bayar di sini](https://evil.example),', (string) (new OrderConfirmation($order))->toMail($order)->render());
    }

    public function test_discord_message_escapes_links_and_disables_mentions(): void
    {
        config(['studio.discord_webhook' => 'https://discord.test/webhook']);
        Http::fake();
        Notification::fake();

        $this->post('/order', $this->orderPayload(['name' => '[klik](https://evil.example) @everyone']));

        Http::assertSent(function (Request $request) {
            $name = $request['embeds'][0]['fields'][0]['value'];

            return $request['allowed_mentions'] === ['parse' => []]
                && $name === '\[klik\](https://evil.example) @everyone';
        });
    }

    public function test_too_many_addons_are_rejected_without_a_server_error(): void
    {
        Notification::fake();

        $this->post('/order', $this->orderPayload(['addons' => array_fill_keys(range(1, 5000), '1')]))
            ->assertSessionHasErrors('addons');

        $this->assertSame(0, Order::count());
    }

    public function test_thanks_page_only_opens_in_the_browser_that_sent_the_order(): void
    {
        Notification::fake();

        $this->post('/order', $this->orderPayload());
        $code = Order::firstOrFail()->code;

        $this->assertMatchesRegularExpression('/^PW-\d{4}-[A-Z0-9]{6}$/', $code);
        $this->get(route('orders.thanks', $code))->assertOk()->assertSee($code);

        $this->flushSession();
        $this->get(route('orders.thanks', $code))->assertNotFound();
    }

    public function test_validation_errors_do_not_use_up_the_order_limit(): void
    {
        Notification::fake();

        for ($i = 0; $i < 8; $i++) {
            $this->post('/order', $this->orderPayload(['description' => 'pendek']))->assertSessionHasErrors('description');
        }

        $this->post('/order', $this->orderPayload())->assertRedirect();
        $this->assertSame(1, Order::count());
    }

    public function test_all_attempts_are_still_capped(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->post('/order', $this->orderPayload(['description' => 'pendek']));
        }

        $this->post('/order', $this->orderPayload())->assertStatus(429);
    }

    public function test_tracking_ignores_email_letter_case(): void
    {
        Notification::fake();

        $this->post('/order', $this->orderPayload(['email' => 'Mochi@Example.com']));
        $code = Order::firstOrFail()->code;

        $this->post('/track', ['code' => strtolower($code), 'email' => ' mochi@example.COM '])
            ->assertOk()
            ->assertSee('Order '.$code);
    }

    public function test_tracking_needs_the_matching_email(): void
    {
        Notification::fake();

        $this->post('/order', $this->orderPayload());
        $code = Order::firstOrFail()->code;

        $this->post('/track', ['code' => $code, 'email' => 'orang-lain@example.com'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_double_submit_creates_one_order(): void
    {
        Notification::fake();

        $first = $this->post('/order', $this->orderPayload());
        $second = $this->post('/order', $this->orderPayload(['email' => 'MOCHI@example.com']));

        $this->assertSame(1, Order::count());
        $this->assertSame($first->headers->get('Location'), $second->headers->get('Location'));
        Notification::assertSentOnDemandTimes(OrderConfirmation::class, 1);

        // Order baru dengan brief lain tetap boleh.
        $this->post('/order', $this->orderPayload(['description' => 'Alert untuk follow dan sub, gaya pixel.']));
        $this->assertSame(2, Order::count());
    }

    public function test_code_collision_is_retried(): void
    {
        Notification::fake();
        $this->post('/order', $this->orderPayload());
        $taken = Order::firstOrFail()->code;

        // Simulasi dua order yang mendapat kode sama di saat bersamaan: percobaan pertama memakai kode yang sudah ada.
        $first = true;
        Order::creating(function (Order $order) use (&$first, $taken) {
            if ($first) {
                $first = false;
                $order->code = $taken;
            }
        });

        $this->post('/order', $this->orderPayload(['email' => 'lain@example.com']))->assertRedirect();

        $this->assertSame(2, Order::count());
        $this->assertNotSame($taken, Order::latest('id')->firstOrFail()->code);
    }

    public function test_admin_is_warned_when_slots_are_overbooked(): void
    {
        Setting::put('slots_total', 1);
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret-password']));
        Notification::fake();

        $this->post('/order', $this->orderPayload());
        $this->post('/order', $this->orderPayload(['email' => 'kedua@example.com']));
        [$one, $two] = Order::orderBy('id')->get()->all();
        $one->update(['status' => 'in_progress']);

        Livewire::test(EditOrder::class, ['record' => $two->getRouteKey()])
            ->fillForm(['status' => 'waiting_payment', 'notify_client' => false])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Slot terpakai 2 dari 1');

        $this->assertSame('waiting_payment', $two->fresh()->status);
    }

    public function test_portfolio_links_are_only_http(): void
    {
        PortfolioItem::create(['title' => 'Demo', 'category' => 'chatbox', 'is_published' => true, 'is_featured' => true, 'external_url' => 'javascript:alert(1)']);

        $this->get('/work')->assertOk()->assertSee('Demo')->assertDontSee('href="javascript:', false);
    }

    public function test_public_pages_send_a_content_security_policy(): void
    {
        foreach (['/', '/services', '/order', '/track', '/halaman-tidak-ada'] as $url) {
            $csp = $this->get($url)->headers->get('Content-Security-Policy');

            $this->assertStringContainsString("script-src 'self'", $csp, $url);
            $this->assertStringNotContainsString("'unsafe-eval'", $csp, $url);
            $this->assertStringContainsString("object-src 'none'", $csp, $url);
            $this->assertStringContainsString("font-src 'self' https://fonts.gstatic.com", $csp, $url);
        }
    }

    public function test_admin_panel_has_no_content_security_policy(): void
    {
        $this->get('/admin/login')->assertOk()->assertHeaderMissing('Content-Security-Policy');

        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret-password']));
        $this->get('/admin/orders')->assertOk()->assertHeaderMissing('Content-Security-Policy');
    }

    public function test_client_and_admin_text_is_escaped_everywhere(): void
    {
        Notification::fake();

        $this->post('/order', $this->orderPayload([
            'name' => substr(self::XSS, 0, 80),
            'contact_handle' => self::XSS,
            'budget' => substr(self::XSS, 0, 60),
            'description' => self::XSS.' brief yang cukup panjang',
        ]));
        $order = Order::firstOrFail();

        foreach (['tagline', 'about', 'contact_x', 'payment_info', 'terms', 'privacy'] as $key) {
            Setting::put($key, self::XSS);
        }
        Setting::put('faq', [['question' => self::XSS, 'answer' => self::XSS]]);
        PortfolioItem::create(['title' => self::XSS, 'category' => 'chatbox', 'client_label' => self::XSS, 'is_published' => true, 'is_featured' => true]);

        $pages = [
            $this->post('/track', ['code' => $order->code, 'email' => $order->email]),
            ...array_map(fn ($url) => $this->get($url), ['/', '/work', '/services', '/terms', '/privacy', '/order']),
        ];
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret-password']));
        $pages[] = $this->get('/admin/orders');
        $pages[] = $this->get('/admin/orders/'.$order->id.'/edit');

        foreach ($pages as $response) {
            $response->assertOk()
                ->assertDontSee('<img src=x onerror', false)
                ->assertDontSee('<script>alert(2)', false);
        }

        $html = (string) (new NewOrderReceived($order))->toMail($order)->render();
        $this->assertStringNotContainsString('<img src=x onerror', $html);
    }
}
