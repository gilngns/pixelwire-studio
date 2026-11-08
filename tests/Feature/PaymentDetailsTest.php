<?php

namespace Tests\Feature;

use App\Filament\Pages\StudioSettings;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentDetailsTest extends TestCase
{
    use RefreshDatabase;

    private const DETAILS = "BNI 0123456789 a.n. Pixelwire\nDANA 081200000000";

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put('payment_details', self::DETAILS);
    }

    private function track(string $status): \Illuminate\Testing\TestResponse
    {
        $order = $this->makeOrder($status);

        return $this->post('/track', ['code' => $order->code, 'email' => $order->email]);
    }

    public function test_account_details_are_never_on_public_pages(): void
    {
        foreach (['/', '/services', '/order', '/terms', '/privacy'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('0123456789')->assertDontSee('081200000000');
        }

        $this->get('/services')->assertSee('Indonesia: bank transfer or e-wallet (IDR).');
    }

    public function test_track_page_shows_details_only_after_the_order_is_confirmed(): void
    {
        foreach (['waiting_payment', 'in_progress', 'revision'] as $status) {
            $this->track($status)->assertOk()->assertSee('Payment details')->assertSee('BNI 0123456789 a.n. Pixelwire')->assertSee('DANA 081200000000');
        }

        foreach (['new', 'quoted', 'completed', 'cancelled'] as $status) {
            $this->track($status)->assertOk()->assertDontSee('0123456789');
        }
    }

    public function test_track_page_needs_the_right_email_to_see_details(): void
    {
        $order = $this->makeOrder('waiting_payment');

        $this->post('/track', ['code' => $order->code, 'email' => 'orang-lain@example.com'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->followRedirects($this->post('/track', ['code' => $order->code, 'email' => 'orang-lain@example.com']))
            ->assertDontSee('0123456789');
    }

    public function test_waiting_payment_email_contains_the_private_details(): void
    {
        $order = $this->makeOrder('waiting_payment');
        $lines = (new OrderStatusUpdated($order))->toMail(new AnonymousNotifiable)->introLines;

        $this->assertContains('BNI 0123456789 a.n. Pixelwire', $lines);
        $this->assertContains('DANA 081200000000', $lines);
        $this->assertContains("Write your order code ({$order->code}) in the transfer note.", $lines);
        $this->assertNotContains('Indonesia: bank transfer or e-wallet (IDR).', $lines);

        app()->setLocale('id');
        $lines = (new OrderStatusUpdated($order))->toMail(new AnonymousNotifiable)->introLines;
        $this->assertContains("Tulis kode order ({$order->code}) di berita transfer.", $lines);
    }

    public function test_email_falls_back_to_public_methods_when_details_are_empty(): void
    {
        Setting::put('payment_details', '');
        $lines = (new OrderStatusUpdated($this->makeOrder('waiting_payment')))->toMail(new AnonymousNotifiable)->introLines;

        $this->assertContains('Indonesia: bank transfer or e-wallet (IDR).', $lines);
        $this->assertNotContains('You can also see these details on the order status page.', $lines);
    }

    public function test_admin_can_save_private_details(): void
    {
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret-password']));

        Livewire::test(StudioSettings::class)
            ->assertFormSet(['payment_details' => self::DETAILS])
            ->fillForm(['payment_details' => 'BNI 999 a.n. Studio'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('BNI 999 a.n. Studio', Setting::get('payment_details'));
        $this->get('/admin/studio-settings')->assertSee('Detail rekening (privat)');
    }
}
