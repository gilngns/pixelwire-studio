<?php

namespace Tests\Feature;

use App\Models\Addon;
use App\Models\Order;
use App\Models\Service;
use App\Support\PriceEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PriceListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Notification::fake();
    }

    private function service(string $slug): Service
    {
        return Service::where('slug', $slug)->sole();
    }

    /** @param array<string, int> $addons slug => jumlah */
    private function selections(array $addons): array
    {
        return Addon::whereIn('slug', array_keys($addons))->get()
            ->mapWithKeys(fn (Addon $addon) => [$addon->id => $addons[$addon->slug]])
            ->all();
    }

    public static function estimates(): array
    {
        return [
            'paket + add-on harga tetap (IDR)' => ['chat-box-basic', ['video-preview-showcase' => 1], 'IDR', 420000],
            'paket + add-on harga tetap (USD)' => ['chat-box-basic', ['video-preview-showcase' => 1], 'USD', 35],
            'paket + add-on persen (IDR)' => ['chat-box-role-frames', ['version-for-a-second-platform' => 1], 'IDR', 770000],
            'paket + add-on persen (USD)' => ['chat-box-role-frames', ['version-for-a-second-platform' => 1], 'USD', 63],
            'persen dibulatkan ke $1' => ['single-alert', ['express-delivery' => 1], 'USD', 23],
            'persen dibulatkan ke Rp1.000' => ['single-alert', ['express-delivery' => 1], 'IDR', 225000],
            'bundle + persen + per revisi + gratis (IDR)' => ['starter-pack', ['express-delivery' => 1, 'extra-revision' => 2, 'installation-help-over-a-call' => 1], 'IDR', 1600000],
            'bundle + persen + per revisi + gratis (USD)' => ['starter-pack', ['express-delivery' => 1, 'extra-revision' => 2, 'installation-help-over-a-call' => 1], 'USD', 138],
            'jumlah dibatasi maksimal add-on' => ['chat-box-basic', ['extra-revision' => 9], 'IDR', 550000],
        ];
    }

    #[DataProvider('estimates')]
    public function test_estimate(string $package, array $addons, string $currency, float $expected): void
    {
        $estimate = PriceEstimator::estimate($this->service($package), $this->selections($addons), $currency);

        $this->assertEquals($expected, $estimate['total']);
    }

    public function test_starting_price_package_is_marked_as_starting(): void
    {
        $estimate = PriceEstimator::estimate($this->service('simple-interactive-widget'), [], 'IDR');

        $this->assertEquals(350000, $estimate['total']);
        $this->assertTrue($estimate['is_starting']);
    }

    public function test_without_a_package_there_is_no_total_and_percent_addons_have_no_amount(): void
    {
        $estimate = PriceEstimator::estimate(null, $this->selections(['video-preview-showcase' => 1, 'express-delivery' => 1]), 'IDR');

        $this->assertNull($estimate['total']);
        $amounts = array_column($estimate['addons'], 'amount', 'name');
        $this->assertEquals(120000, $amounts['Video preview / showcase']);
        $this->assertNull($amounts['Express delivery']);
    }

    public function test_order_saves_addons_and_server_side_estimate(): void
    {
        $bundle = $this->service('starter-pack');
        $ids = Addon::pluck('id', 'slug');

        $this->post('/order', $this->orderPayload([
            'service_id' => $bundle->id,
            'currency' => 'USD',
            'addons' => [$ids['express-delivery'] => '1', $ids['extra-revision'] => '1'],
            'addon_quantity' => [$ids['extra-revision'] => 2],
            'estimated_total' => 1, // angka palsu dari browser harus diabaikan
        ]))->assertRedirect();

        $order = Order::sole();
        // $85 + express 50% ($42,50 dibulatkan jadi $43) + 2 revisi tambahan x $5
        $this->assertEquals(138, (float) $order->estimated_total);
        $this->assertFalse($order->estimate_is_starting);
        $this->assertSame(['Express delivery', 'Extra revision'],collect($order->addons)->pluck('name')->sort()->values()->all());
        $this->assertSame(2, collect($order->addons)->firstWhere('name', 'Extra revision')['quantity']);
        $this->assertSame('$138', $order->estimatedTotalLabel());
    }

    public function test_inactive_or_unknown_addons_are_ignored(): void
    {
        $addon = Addon::where('slug', 'video-preview-showcase')->sole();
        $addon->update(['is_active' => false]);

        $this->post('/order', $this->orderPayload([
            'service_id' => $this->service('goal-bar')->id,
            'addons' => [$addon->id => '1', 9999 => '1'],
        ]));

        $order = Order::sole();
        $this->assertNull($order->addons);
        $this->assertEquals(250000, (float) $order->estimated_total);
    }

    public function test_services_page_groups_packages_and_shows_bundle_and_addons(): void
    {
        $this->get('/services?lang=en&cur=idr')
            ->assertOk()
            ->assertSeeInOrder(['Chat box', 'Chat box basic', 'Alerts', 'Single alert', 'Timers &amp; goals', 'Subathon timer', 'Interactive', 'Complex interactive widget', 'Bundles', 'Starter pack', 'Add-ons'], false)
            ->assertSee('Design is not included')
            ->assertSee('Rp1.150.000')
            ->assertSee('Save Rp150.000')
            ->assertSee('+Rp120.000')
            ->assertSee('+40% of the package price')
            ->assertSee('Free')
            ->assertSee('from');

        $this->get('/services?lang=id&cur=usd')
            ->assertSee('$95')
            ->assertSee('$85')
            ->assertSee('Hemat $10')
            ->assertSee('+50% dari harga paket')
            ->assertSee('Gratis')
            ->assertSee('mulai');
    }

    public function test_order_form_lists_packages_bundles_addons_and_estimate_box(): void
    {
        $this->get('/order?service=starter-pack&lang=id')
            ->assertOk()
            ->assertSee('<optgroup label="Bundle">', false)
            ->assertSee('Starter pack (Rp1.000.000)')
            ->assertSee('data-starting="1"', false)
            ->assertSee('Add-on (opsional)')
            ->assertSee('Estimasi total')
            ->assertSee('Harga final dikirim bersama penawaran.');
    }

    public function test_home_only_shows_featured_items(): void
    {
        $this->get('/?lang=en')
            ->assertSee('Chat box basic')
            ->assertSee('Starter pack')
            ->assertDontSee('Countdown timer');
    }
}
