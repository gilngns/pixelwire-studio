<?php

namespace Tests\Feature;

use App\Filament\Pages\StudioSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class InfoPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_page_is_linked_from_the_footer_and_order_form(): void
    {
        $this->get('/privacy')
            ->assertOk()
            ->assertSee('Privacy policy')
            ->assertSee('What I collect')
            ->assertSee('No analytics or advertising cookies.');

        $this->get('/')->assertSee('href="'.route('privacy').'"', false);
        $this->get('/order')->assertSee('Your details are only used to handle this order.', false);
        $this->get('/order?lang=id')->assertSee('Data kamu hanya dipakai untuk mengurus order ini.', false);
    }

    public function test_privacy_text_comes_from_settings_without_raw_html(): void
    {
        Setting::put('privacy', "## Data\n\n- Hanya email.\n\n<script>alert(1)</script>");

        $this->get('/privacy')
            ->assertSee('<h2>Data</h2>', false)
            ->assertSee('Hanya email.')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_faq_shows_on_home_and_services(): void
    {
        foreach (['/', '/services'] as $url) {
            $this->get($url)
                ->assertSee('id="faq"', false)
                ->assertSee('Which platforms do the widgets work with?')
                ->assertSee('Installation help over a call is a free add-on');
        }

        $this->get('/?lang=id')->assertSee('Tanya jawab');
    }

    public function test_faq_is_hidden_when_empty(): void
    {
        Setting::put('faq', []);

        $this->get('/')->assertOk()->assertDontSee('id="faq"', false);
        $this->get('/services')->assertOk()->assertDontSee('id="faq"', false);
    }

    public function test_admin_can_edit_the_faq_and_privacy_text(): void
    {
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret-password']));

        Livewire::test(StudioSettings::class)
            ->set('data.faq', [
                ['question' => 'Do you take rush orders?', 'answer' => 'Yes, with the express add-on.'],
            ])
            ->set('data.privacy', '## Short version')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([['question' => 'Do you take rush orders?', 'answer' => 'Yes, with the express add-on.']], Setting::get('faq'));
        $this->get('/')->assertSee('Do you take rush orders?')->assertDontSee('Which platforms do the widgets work with?');
        $this->get('/privacy')->assertSee('Short version');
    }

    public function test_sitemap_lists_public_pages_in_both_languages(): void
    {
        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());
        $locs = array_map('strval', $xml->xpath('//*[local-name()="loc"]'));

        $this->assertContains(url('/').'/?lang=en', $locs);
        $this->assertContains(route('services').'?lang=id', $locs);
        $this->assertContains(route('privacy').'?lang=en', $locs);
        $this->assertCount(12, $locs);
        $this->assertNotContains(route('orders.track').'?lang=en', $locs);
    }

    public function test_robots_txt_points_to_the_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_unknown_page_shows_the_styled_404(): void
    {
        $this->get('/halaman-tidak-ada')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Back to home')
            ->assertSee('<meta name="robots" content="noindex">', false);

        $this->get('/order/thanks/PW-TIDAK-ADA')->assertNotFound()->assertSee('Page not found');
    }

    public function test_too_many_order_attempts_show_the_styled_429(): void
    {
        Notification::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/order', $this->orderPayload(['email' => "mochi{$i}@example.com"]));
        }

        $this->post('/order', $this->orderPayload())
            ->assertStatus(429)
            ->assertSee('Too many tries');
    }

    public function test_other_error_pages_render(): void
    {
        foreach (['419' => 'This page expired', '500' => 'Something went wrong', '503' => 'Back soon'] as $code => $title) {
            $html = view("errors.$code")->render();

            $this->assertStringContainsString($title, $html);
            $this->assertStringContainsString('Back to home', $html);
        }
    }
}
