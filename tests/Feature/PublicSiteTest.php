<?php

namespace Tests\Feature;

use App\Models\PortfolioItem;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return [['/'], ['/work'], ['/services'], ['/terms'], ['/order'], ['/track']];
    }

    #[DataProvider('pages')]
    public function test_public_pages_open_in_both_languages(string $path): void
    {
        $this->makeService();

        $this->get($path.'?lang=en')->assertOk();
        $this->get($path.'?lang=id')->assertOk();
    }

    public function test_english_is_the_default_even_for_indonesian_browsers(): void
    {
        $this->withHeader('Accept-Language', 'id-ID,id;q=0.9')
            ->get('/')
            ->assertOk()
            ->assertSee('How ordering works');
    }

    public function test_language_toggle_switches_to_indonesian_and_is_remembered(): void
    {
        $this->get('/?lang=id')->assertSee('Cara order');
        $this->get('/services')->assertSee('Layanan &amp; harga', false);
    }

    public function test_currency_defaults_follow_language_and_toggle_is_remembered(): void
    {
        $this->makeService();

        $this->get('/services?lang=en')->assertSee('$30')->assertDontSee('Rp350.000');
        $this->get('/services?lang=id')->assertSee('Rp350.000');

        $this->get('/services?cur=usd')->assertSee('$30');
        $this->get('/order')->assertSee('$30');
    }

    public function test_deposit_and_reply_time_come_from_settings(): void
    {
        Setting::put('deposit_percent', 30);
        Setting::put('reply_days_min', 2);
        Setting::put('reply_days_max', 4);

        $this->get('/?lang=en')
            ->assertSee('A 30% deposit reserves your slot')
            ->assertSee('I reply within 2 to 4 days');

        $this->get('/?lang=id')
            ->assertSee('DP 30% untuk mengamankan slot')
            ->assertSee('dalam 2 sampai 4 hari');
    }

    public function test_work_page_filters_by_category_and_has_empty_state(): void
    {
        $this->get('/work')->assertSee('No demos here yet');

        PortfolioItem::create(['title' => 'Sakura alert set', 'category' => 'alerts', 'is_published' => true]);
        PortfolioItem::create(['title' => 'Hidden draft', 'category' => 'alerts', 'is_published' => false]);

        $this->get('/work')->assertSee('Sakura alert set')->assertDontSee('Hidden draft');
        $this->get('/work?category=alerts')->assertSee('Sakura alert set');
        $this->get('/work?category=chatbox')->assertDontSee('Sakura alert set')->assertSee('No demos here yet');
    }

    public function test_order_button_on_services_preselects_the_package(): void
    {
        $service = $this->makeService();

        $this->get('/order?service='.$service->slug)
            ->assertOk()
            ->assertSee('value="'.$service->id.'" selected', false);
    }
}
