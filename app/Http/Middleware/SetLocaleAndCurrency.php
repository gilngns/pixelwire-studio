<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bahasa (en/id) dan mata uang (IDR/USD) dipilih lewat query string
 * ?lang=id atau ?cur=usd, lalu disimpan di session.
 */
class SetLocaleAndCurrency
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();

        if (in_array($request->query('lang'), ['en', 'id'], true)) {
            $session->put('locale', $request->query('lang'));
        }

        if (in_array(strtoupper((string) $request->query('cur')), ['IDR', 'USD'], true)) {
            $session->put('currency', strtoupper($request->query('cur')));
        }

        // Bahasa default English; pengunjung bisa pindah ke Indonesia lewat toggle EN/ID.
        $locale = $session->get('locale', 'en');
        App::setLocale($locale);

        $currency = $session->get('currency', $locale === 'id' ? 'IDR' : 'USD');

        View::share('currency', $currency);
        View::share('locale', $locale);

        return $next($request);
    }
}
