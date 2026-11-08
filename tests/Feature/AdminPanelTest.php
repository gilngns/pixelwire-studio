<?php

namespace Tests\Feature;

use App\Filament\Pages\StudioSettings;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Widgets\StudioStats;
use App\Models\Addon;
use App\Models\PortfolioItem;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret-password']);
    }

    public function test_guests_are_sent_to_login_and_there_is_no_registration(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
        $this->get('/admin/register')->assertNotFound();
    }

    public static function adminPages(): array
    {
        return [
            ['/admin'],
            ['/admin/orders'],
            ['/admin/services'],
            ['/admin/services/create'],
            ['/admin/portfolio-items'],
            ['/admin/portfolio-items/create'],
            ['/admin/studio-settings'],
            ['/admin/addons'],
            ['/admin/addons/create'],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_admin_pages_open(string $path): void
    {
        $this->actingAs($this->admin())->get($path)->assertOk();
    }

    public function test_record_edit_pages_open(): void
    {
        $this->actingAs($this->admin());
        $order = $this->makeOrder();
        $service = $this->makeService();
        $item = PortfolioItem::create(['title' => 'Demo', 'category' => 'chatbox', 'is_published' => true]);

        $this->get("/admin/orders/{$order->id}/edit")->assertOk()->assertSee($order->code);
        $this->get("/admin/services/{$service->id}/edit")->assertOk();
        $this->get("/admin/portfolio-items/{$item->id}/edit")->assertOk();

        $addon = Addon::create(['name' => 'Extra revision', 'pricing_type' => 'fixed', 'price_idr' => 50000, 'price_usd' => 5]);
        $this->get("/admin/addons/{$addon->id}/edit")->assertOk();
    }

    public function test_dashboard_shows_studio_stats_and_badge_counts_new_orders(): void
    {
        $this->makeOrder('new');
        $this->makeOrder('new');
        $this->makeOrder('in_progress');

        $this->actingAs($this->admin())->get('/admin')->assertOk()->assertSeeLivewire(StudioStats::class);

        // Widget statistik dimuat lazy, jadi isinya dicek dengan merender widget langsung.
        Livewire::test(StudioStats::class)
            ->assertSee('Order baru')
            ->assertSee('Slot terpakai')
            ->assertSee('1 / 5');

        $this->assertSame('2', OrderResource::getNavigationBadge());
    }

    public function test_quote_saved_in_admin_is_visible_when_tracking(): void
    {
        $this->actingAs($this->admin());
        $order = $this->makeOrder();

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(['status' => 'waiting_payment', 'quoted_price' => 600000, 'quoted_currency' => 'IDR'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->post('/track', ['code' => $order->code, 'email' => $order->email])
            ->assertSee('Waiting for payment')
            ->assertSee('Rp600.000');
    }

    public function test_studio_settings_can_be_saved(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(StudioSettings::class)
            ->set('data.commission_open', false)
            ->set('data.slots_total', 3)
            ->set('data.deposit_percent', 40)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse(Setting::get('commission_open'));
        $this->assertEquals(3, Setting::get('slots_total'));
        $this->assertEquals(40, Setting::get('deposit_percent'));
        $this->get('/order')->assertSee('Commissions closed');
    }

    public function test_seeder_creates_admin_price_list_and_default_settings_and_can_run_again(): void
    {
        $this->seed();

        $admin = User::where('email', config('studio.admin.email'))->sole();
        $this->assertTrue(Hash::check(config('studio.admin.password'), $admin->password));
        $this->assertSame(count(config('studio.defaults')), Setting::count());
        $this->assertSame(9, Service::packages()->count());
        $this->assertSame(1, Service::bundles()->count());
        $this->assertSame(5, Addon::count());

        // Pengaturan dari admin tetap aman, tapi price list kembali ke angka resmi di seeder.
        Setting::put('slots_total', 9);
        Service::where('slug', 'chat-box-basic')->update(['price_idr' => 425000]);
        $this->seed();

        $this->assertEquals(9, Setting::get('slots_total'));
        $this->assertSame(300000, Service::where('slug', 'chat-box-basic')->sole()->price_idr);
        $this->assertSame(10, Service::count());
        $this->assertSame(5, Addon::count());
        $this->assertSame(1, User::count());
    }

    public function test_seeder_removes_the_old_sample_services(): void
    {
        $unused = $this->makeService(['name' => 'Interactive widget', 'slug' => 'interactive-widget', 'is_featured' => true]);
        $used = $this->makeService(['name' => 'Timer or goal bar', 'slug' => 'timer-or-goal-bar']);
        $order = $this->makeOrder('completed', ['service_id' => $used->id]);

        $this->seed();

        $this->assertNull($unused->fresh());
        // Yang sudah dipakai order tetap ada (nonaktif), supaya nama layanan di order lama tetap terbaca.
        $this->assertFalse($used->fresh()->is_active);
        $this->assertSame('Timer or goal bar', $order->fresh()->service->name);
        $this->get('/services')->assertDontSee('Interactive widget')->assertDontSee('Timer or goal bar');
    }

    public function test_admin_can_create_a_percentage_addon(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(\App\Filament\Resources\AddonResource\Pages\CreateAddon::class)
            ->fillForm(['name' => 'Rush fee', 'slug' => 'rush-fee', 'pricing_type' => 'percent', 'percent' => 25, 'max_quantity' => 1])
            ->call('create')
            ->assertHasNoFormErrors();

        $addon = Addon::where('slug', 'rush-fee')->sole();
        $this->assertSame('percent', $addon->pricing_type);
        $this->assertSame(25, $addon->percent);
    }

    public function test_admin_can_create_a_bundle_without_category(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(\App\Filament\Resources\ServiceResource\Pages\CreateService::class)
            ->fillForm([
                'type' => 'bundle', 'name' => 'Alert duo', 'slug' => 'alert-duo', 'summary' => 'Two alerts.',
                'price_idr' => 250000, 'price_usd' => 22, 'compare_price_idr' => 300000, 'compare_price_usd' => 30, 'revisions' => 2,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $bundle = Service::where('slug', 'alert-duo')->sole();
        $this->assertTrue($bundle->isBundle());
        $this->assertNull($bundle->category);
        $this->assertSame('Rp300.000', $bundle->comparePriceFor('IDR'));
    }

    public function test_order_detail_shows_addons_estimate_and_partner_discount(): void
    {
        $this->actingAs($this->admin());
        $order = $this->makeOrder('new', [
            'estimated_total' => 1600000,
            'addons' => [['name' => 'Express delivery', 'pricing_type' => 'percent', 'percent' => 50, 'quantity' => 1, 'amount' => 500000]],
        ]);

        $this->get("/admin/orders/{$order->id}/edit")
            ->assertOk()
            ->assertSee('Express delivery (Rp500.000)')
            ->assertSee('Rp1.600.000');

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(['partner_discount_percent' => 10])
            ->assertSee('Rp1.440.000 (dari estimasi form)')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals(10, $order->fresh()->partner_discount_percent);
        $this->get('/track')->assertDontSee('partner');
    }

    public function test_partner_discount_uses_the_quoted_price_once_it_is_filled(): void
    {
        $this->actingAs($this->admin());
        $order = $this->makeOrder('quoted', ['estimated_total' => 890000, 'quoted_price' => 850000, 'quoted_currency' => 'IDR']);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(['partner_discount_percent' => 10])
            ->assertSee('Rp765.000 (dari harga penawaran)')
            ->assertDontSee('Rp801.000');
    }

    public function test_order_form_shows_whole_numbers_and_indonesian_statuses(): void
    {
        $this->actingAs($this->admin());
        $order = $this->makeOrder('quoted', ['quoted_price' => 850000, 'quoted_currency' => 'IDR', 'partner_discount_percent' => 10]);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->assertFormSet(['quoted_price' => '850000', 'partner_discount_percent' => '10']);

        $this->get('/admin/orders')->assertSee('Penawaran dikirim')->assertDontSee('Quote sent');
        $this->assertSame('Menunggu pembayaran', OrderResource::statusOptions()['waiting_payment']);
        $this->assertSame('12.5', OrderResource::plainNumber('12.50'));
    }

    public function test_dates_use_jakarta_time(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-25 17:29:00', 'UTC'));
        $this->actingAs($this->admin());
        $order = $this->makeOrder();

        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->get("/admin/orders/{$order->id}/edit")->assertSee('26 Oct 2026 00:29');
    }

    public function test_seeder_never_creates_an_admin_with_a_default_password(): void
    {
        config(['studio.admin.email' => null, 'studio.admin.password' => null]);

        $this->seed();

        $this->assertSame(0, User::count());
        $this->assertSame(10, Service::count());
    }

    public function test_seeder_refuses_weak_admin_password_in_production(): void
    {
        config(['studio.admin.email' => 'admin@example.com', 'studio.admin.password' => 'ganti-password-ini']);
        $this->app['env'] = 'production';

        $this->expectException(\RuntimeException::class);

        try {
            // Dipanggil langsung karena `db:seed` di production meminta konfirmasi interaktif.
            $this->app->make(\Database\Seeders\DatabaseSeeder::class)->run();
        } finally {
            $this->assertSame(0, User::count());
        }
    }

    public function test_security_headers_are_sent_on_public_and_admin_pages(): void
    {
        foreach (['/', '/admin/login'] as $path) {
            $this->get($path)
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
    }
}
