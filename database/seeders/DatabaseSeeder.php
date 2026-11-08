<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();

        $this->seedPriceList();

        // Pengaturan default dari config/studio.php. Nilai yang sudah diubah dari admin tidak ditimpa.
        foreach (config('studio.defaults') as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('studio.settings');
    }

    /**
     * Price list resmi. Memakai updateOrCreate berdasarkan slug, jadi menjalankan seeder
     * lagi akan mengembalikan harga ke angka di bawah ini.
     * Harga untuk integrasi saja: klien membawa aset desain jadi, studio tidak membuat desain.
     */
    private function seedPriceList(): void
    {
        $packages = [
            ['chatbox', 'Chat box basic', 'A chat box coded to match your overlay, with badges, emotes and an entry animation.',
                ['One display style', 'Badges and emotes', 'Message entry animation'], 300000, 25, 2, '3 to 5 days', false, true],
            ['chatbox', 'Chat box role frames', 'Different frames for each chat role, with colors you can change yourself.',
                ['Separate PNG frame for broadcaster, mods, VIPs, subs and viewers', 'Fields to change the colors yourself'], 550000, 45, 3, '5 to 7 days', false, true],
            ['alerts', 'Single alert', 'One animated alert, such as a follow or sub alert.',
                ['One alert type', 'Custom animation'], 150000, 15, 2, '2 to 3 days', false, false],
            ['alerts', 'Alert set', 'A full set of animated alerts with sound and tiers.',
                ['Up to 6 alert types', 'Sound support', 'Tiered alerts by amount'], 600000, 50, 3, '5 to 7 days', false, true],
            ['timer', 'Countdown timer', 'A countdown you can adjust without opening OBS.',
                ['Duration set from StreamElements fields, no need to open OBS'], 250000, 20, 2, '2 to 4 days', false, false],
            ['timer', 'Goal bar', 'A progress bar for follower, sub or donation goals.',
                ['Tracks followers, subs or donations', 'Milestones along the bar'], 250000, 20, 2, '2 to 4 days', false, false],
            ['timer', 'Subathon timer', 'A subathon timer that grows with every sub, cheer and donation.',
                ['Time added automatically from subs, cheers and donations'], 500000, 40, 3, '4 to 6 days', false, true],
            ['interactive', 'Simple interactive widget', 'Small chat-driven widgets like dice rolls, pickers and command effects.',
                ['Dice or random roll', 'Picker', 'Effects triggered by chat commands'], 350000, 30, 2, 'Depends on scope', true, false],
            ['interactive', 'Complex interactive widget', 'Companions that react to chat, mini games and bigger ideas.',
                ['Characters or companions that react to chat', 'Mini games'], 800000, 70, 3, 'Depends on scope', true, false],
        ];

        foreach ($packages as $i => [$category, $name, $summary, $features, $idr, $usd, $revisions, $turnaround, $starting, $featured]) {
            Service::updateOrCreate(['slug' => Str::slug($name)], [
                'type' => 'package', 'name' => $name, 'category' => $category, 'summary' => $summary,
                'features' => $features, 'price_idr' => $idr, 'price_usd' => $usd,
                'compare_price_idr' => null, 'compare_price_usd' => null, 'price_is_starting' => $starting,
                'revisions' => $revisions, 'turnaround' => $turnaround,
                'is_featured' => $featured, 'is_active' => true, 'sort_order' => $i + 1,
            ]);
        }

        Service::updateOrCreate(['slug' => 'starter-pack'], [
            'type' => 'bundle', 'name' => 'Starter pack', 'category' => null,
            'summary' => 'Chat box role frames and an alert set together, for less.',
            'features' => ['Chat box role frames', 'Alert set'],
            'price_idr' => 1000000, 'price_usd' => 85, 'compare_price_idr' => 1150000, 'compare_price_usd' => 95,
            'price_is_starting' => false, 'revisions' => 3, 'turnaround' => null,
            'is_featured' => true, 'is_active' => true, 'sort_order' => 100,
        ]);

        // Layanan contoh dari versi awal dihapus. Yang sudah dipakai di order hanya dinonaktifkan,
        // supaya nama layanan di order lama tetap terbaca.
        $oldServices = Service::whereIn('slug', ['chat-box', 'chat-box-with-role-frames', 'timer-or-goal-bar', 'interactive-widget']);
        (clone $oldServices)->whereDoesntHave('orders')->delete();
        $oldServices->update(['is_active' => false, 'is_featured' => false]);

        $addons = [
            ['Video preview / showcase', 'A short showcase video of your widget, ready to post.', 'fixed', 120000, 10, null, 1],
            ['Extra revision', 'Per revision, on top of the revisions included in your package.', 'fixed', 50000, 5, null, 5],
            ['Version for a second platform', 'Get the widget for both StreamElements and Streamlabs.', 'percent', 0, 0, 40, 1],
            ['Express delivery', 'Delivered in about half the usual time.', 'percent', 0, 0, 50, 1],
            ['Installation help over a call', 'I help you set the widget up over a call.', 'free', 0, 0, null, 1],
        ];

        foreach ($addons as $i => [$name, $description, $pricing, $idr, $usd, $percent, $max]) {
            Addon::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'description' => $description, 'pricing_type' => $pricing,
                'price_idr' => $idr, 'price_usd' => $usd, 'percent' => $percent, 'max_quantity' => $max,
                'is_active' => true, 'sort_order' => $i + 1,
            ]);
        }
    }

    /**
     * Akun admin dari ADMIN_EMAIL dan ADMIN_PASSWORD. Tidak ada password bawaan:
     * kalau salah satunya kosong, akun admin tidak dibuat atau diubah.
     */
    private function seedAdmin(): void
    {
        ['name' => $name, 'email' => $email, 'password' => $password] = config('studio.admin');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL atau ADMIN_PASSWORD kosong, akun admin dilewati.');

            return;
        }

        if (app()->isProduction() && (strlen($password) < 12 || $password === 'ganti-password-ini')) {
            throw new RuntimeException('ADMIN_PASSWORD di .env minimal 12 karakter dan bukan nilai contoh.');
        }

        User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => $password]);
    }
}
