<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\PortfolioItem;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'featuredWork' => PortfolioItem::published()->where('is_featured', true)->take(6)->get(),
            // Beranda hanya menampilkan layanan yang ditandai "Tonjolkan di beranda".
            'services' => Service::active()->where('is_featured', true)->get(),
        ]);
    }

    public function work(Request $request): View
    {
        $categories = config('studio.categories');
        $active = array_key_exists($request->query('category'), $categories) ? $request->query('category') : null;

        $items = PortfolioItem::published()
            ->when($active, fn ($q) => $q->where('category', $active))
            ->get();

        return view('pages.work', compact('items', 'categories', 'active'));
    }

    public function services(): View
    {
        $services = Service::active()->get();

        return view('pages.services', [
            'packages' => $services->where('type', 'package')->groupBy('category'),
            'bundles' => $services->where('type', 'bundle'),
            'addons' => Addon::active()->get(),
            'paymentInfo' => Setting::get('payment_info'),
        ]);
    }

    public function terms(): View
    {
        return view('pages.terms', ['terms' => $this->markdown('terms')]);
    }

    public function privacy(): View
    {
        return view('pages.privacy', ['privacy' => $this->markdown('privacy')]);
    }

    /**
     * Sitemap untuk mesin pencari. URL memakai APP_URL, tiap halaman punya versi EN dan ID.
     */
    public function sitemap(): Response
    {
        $pages = collect(['home', 'work', 'services', 'orders.create', 'terms', 'privacy'])
            // Beranda ditulis dengan garis miring di akhir (https://domain.com/), halaman lain apa adanya.
            ->map(fn (string $route) => $route === 'home' ? rtrim(route('home'), '/').'/' : route($route));

        return response()
            ->view('sitemap', ['pages' => $pages])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /order/thanks/',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Teks markdown dari pengaturan studio (TOS, privasi). HTML mentah dibuang.
     */
    private function markdown(string $key): string
    {
        return Str::markdown((string) Setting::get($key, ''), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
