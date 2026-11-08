<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\Order;
use App\Models\Service;
use App\Support\OrderNotifier;
use App\Support\PriceEstimator;
use App\Support\Studio;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function create(Request $request): View
    {
        $services = Service::active()->get();

        return view('orders.create', [
            'packages' => $services->where('type', 'package')->groupBy('category'),
            'bundles' => $services->where('type', 'bundle'),
            'addons' => Addon::active()->get(),
            'selected' => $services->firstWhere('slug', $request->query('service'))?->id,
            'status' => Studio::status(),
        ]);
    }

    public function store(Request $request, OrderNotifier $notifier): RedirectResponse
    {
        // Honeypot: bot biasanya mengisi semua field, termasuk yang disembunyikan.
        if (filled($request->input('website'))) {
            return redirect()->route('home');
        }

        if (! Studio::isOpen()) {
            return back()->withInput()->with('error', __('Commissions are closed right now, so new orders cannot be sent. Please check back later.'));
        }

        // Batasi jumlah add-on dulu, sebelum aturan per add-on dijalankan untuk setiap item.
        $request->validate([
            'addons' => ['nullable', 'array', 'max:50'],
            'addon_quantity' => ['nullable', 'array', 'max:50'],
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:120'],
            'contact_platform' => ['required', Rule::in(array_keys(config('studio.contact_platforms')))],
            'contact_handle' => ['nullable', 'required_unless:contact_platform,email', 'string', 'max:120'],
            // Link dari klien dibuka admin, jadi hanya http/https yang diterima.
            'channel_url' => ['nullable', 'url:http,https', 'max:255'],
            'currency' => ['required', Rule::in(['IDR', 'USD'])],
            'service_id' => ['nullable', Rule::exists('services', 'id')->where('is_active', true)],
            'platform' => ['required', Rule::in(array_keys(config('studio.platforms')))],
            'has_design' => ['required', Rule::in(array_keys(config('studio.design_options')))],
            'design_link' => ['nullable', 'url:http,https', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'budget' => ['nullable', 'string', 'max:60'],
            'deadline' => ['nullable', 'date', 'after:today'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['boolean'],
            'addon_quantity' => ['nullable', 'array'],
            'addon_quantity.*' => ['nullable', 'integer', 'min:1', 'max:20'],
            'agree_terms' => ['accepted'],
        ], [
            'contact_handle.required_unless' => __('Add your username so I can reach you there.'),
            'agree_terms.accepted' => __('Please read and accept the terms before sending.'),
            'description.min' => __('Describe the widget in at least 20 characters.'),
        ]);

        // Hanya order yang berhasil dikirim yang dihitung, jadi salah isi form tidak membuat klien terblokir.
        $limiterKey = 'orders-sent:'.$request->ip();
        abort_if(RateLimiter::tooManyAttempts($limiterKey, 5), 429);

        // Estimasi selalu dihitung ulang dari database; angka dari browser tidak dipakai.
        $selections = collect($data['addons'] ?? [])
            ->filter()
            ->mapWithKeys(fn ($checked, $id) => [$id => (int) ($data['addon_quantity'][$id] ?? 1)])
            ->all();
        $estimate = PriceEstimator::estimate(
            isset($data['service_id']) ? Service::find($data['service_id']) : null,
            $selections,
            $data['currency'],
        );

        unset($data['agree_terms'], $data['addons'], $data['addon_quantity']);

        $attributes = $data + [
            'status' => 'new',
            'locale' => app()->getLocale(),
            'addons' => $estimate['addons'] ?: null,
            'estimated_total' => $estimate['total'],
            'estimate_is_starting' => $estimate['is_starting'],
        ];

        // Lock per email: kalau tombol kirim terklik dua kali, kiriman kedua menunggu yang pertama
        // selesai, lalu memakai order yang sama alih-alih membuat order dobel.
        [$order, $isNew] = Cache::lock('order-submit:'.sha1(Str::lower($data['email'])), 10)
            ->block(5, function () use ($data, $attributes) {
                $duplicate = Order::whereRaw('lower(email) = ?', [Str::lower($data['email'])])
                    ->where('description', $data['description'])
                    ->where('created_at', '>=', now()->subMinutes(2))
                    ->latest('id')
                    ->first();

                if ($duplicate) {
                    return [$duplicate, false];
                }

                // Kode order dicek unik sebelum disimpan; kalau tetap bentrok dengan order lain
                // yang masuk di saat yang sama, kode baru dibuat dan penyimpanan diulang.
                $order = retry(3, fn () => Order::create($attributes), 0,
                    fn ($e) => $e instanceof UniqueConstraintViolationException);

                return [$order, true];
            });

        if ($isNew) {
            RateLimiter::hit($limiterKey, 3600);
            $notifier->newOrder($order);
        }

        $request->session()->put('placed_order', $order->code);

        return redirect()->route('orders.thanks', $order->code);
    }

    /**
     * Hanya bisa dibuka oleh browser yang mengirim order, supaya halaman ini
     * tidak bisa dipakai untuk menebak kode order milik orang lain.
     */
    public function thanks(Request $request, string $code): View
    {
        abort_unless($request->session()->get('placed_order') === $code, 404);

        return view('orders.thanks', ['code' => $code]);
    }

    public function trackForm(): View
    {
        return view('orders.track');
    }

    public function track(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:120'],
        ]);

        $order = Order::with('service')
            ->where('code', strtoupper(trim($data['code'])))
            // Huruf besar-kecil email tidak dibedakan: Mochi@Example.com sama dengan mochi@example.com.
            ->whereRaw('lower(email) = ?', [Str::lower(trim($data['email']))])
            ->first();

        if (! $order) {
            return back()->withInput()->with('error', __('No order matches that code and email. Check the code in your confirmation email.'));
        }

        return view('orders.track', compact('order'));
    }
}
