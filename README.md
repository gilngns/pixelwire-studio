# Pixelwire Studio

Website commission untuk studio stream widget (chat box, alert, timer, dan widget interaktif). Dibangun dengan Laravel 12, Filament 3, Blade, dan Tailwind CSS 4.

## Fitur

Halaman publik terdiri dari beranda dengan demo chat box yang berjalan langsung, portofolio dengan filter kategori, daftar layanan dan harga (IDR/USD), FAQ, form order, halaman ketentuan, kebijakan privasi, dan halaman lacak order berdasarkan kode order serta email. Tampilannya responsif: di HP dan tablet, menu header pindah ke tombol ☰. Website tersedia dalam bahasa Inggris (default) dan Indonesia, dan pengunjung bisa mengganti bahasa maupun mata uang dari header.

Panel admin di `/admin` dipakai untuk mengelola order (status, harga penawaran, catatan internal), layanan dan harga, portofolio (upload video atau gambar demo), serta pengaturan studio seperti status commission, jumlah slot, tagline, kontak, info pembayaran, TOS, kebijakan privasi, dan FAQ.

Sistem slot berjalan otomatis. Order dengan status menunggu pembayaran, sedang dikerjakan, atau revisi dihitung memakai slot. Ketika slot penuh atau commission ditutup dari admin, form order otomatis terkunci.

Setiap order baru mengirim email ke admin dan email konfirmasi ke klien. Kalau `STUDIO_DISCORD_WEBHOOK` diisi, notifikasinya juga dikirim ke channel Discord.

## Menjalankan di laptop

Kebutuhan: PHP 8.3 (8.2 ke atas juga bisa) dengan ekstensi intl, zip, pdo_sqlite, dan sqlite3, lalu Composer dan Node.js 20 atau lebih baru. Di Laragon atau Windows, ekstensi `pdo_sqlite`, `sqlite3`, dan `zip` biasanya belum aktif. Buka `php.ini`, lalu hapus tanda `;` di depan baris `extension=pdo_sqlite`, `extension=sqlite3`, dan `extension=zip`.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

Website bisa dibuka di http://127.0.0.1:8000 dan admin di http://127.0.0.1:8000/admin. Akun admin dibuat dari nilai `ADMIN_EMAIL` dan `ADMIN_PASSWORD` di `.env`, jadi ganti password-nya sebelum menjalankan `migrate --seed`.

Selama mengembangkan tampilan, jalankan `npm run dev` supaya perubahan CSS langsung terlihat.

Tes otomatis (halaman publik, alur order sampai lacak order, form terkunci saat slot penuh, dan halaman admin) dijalankan dengan `php artisan test`.

Menjalankan `php artisan db:seed` lagi aman, tapi perhatikan apa yang ditimpa:

- Pengaturan studio yang sudah diubah dari admin tidak ditimpa.
- Price list (paket, bundle, add-on) dikembalikan ke angka di `database/seeders/DatabaseSeeder.php`, karena seeder adalah sumber harga resmi. Kalau harga diubah lewat admin, ubah juga di seeder supaya tidak kembali ke harga lama.
- Password akun admin disamakan lagi dengan `ADMIN_PASSWORD` di `.env`. Cara ini juga bisa dipakai untuk reset password admin.

## Setelah instalasi

1. Login ke admin, buka menu **Pengaturan studio**, lalu sesuaikan jumlah slot, persentase DP, estimasi waktu balas brief, tagline, kontak (Discord, X, Instagram), pembayaran, dan TOS. Persentase DP dan waktu balas otomatis dipakai di bagian "Cara order", halaman terima kasih, dan email konfirmasi. Info pembayaran dan TOS ditulis bebas, jadi kalau persentase DP diubah, sesuaikan juga angkanya di kedua teks tersebut.

    Pembayaran punya dua kolom. **Metode pembayaran (publik)** tampil di halaman layanan, jadi tulis metodenya saja (misalnya "transfer bank atau e-wallet"). **Detail rekening (privat)** berisi nomor rekening, e-wallet, atas nama, dan email PayPal. Kolom ini hanya dikirim di email "Menunggu pembayaran" dan tampil di halaman cek status untuk order berstatus menunggu pembayaran, sedang dikerjakan, atau revisi. Halaman cek status hanya bisa dibuka dengan kode order dan email yang cocok.

2. Cek menu **Layanan & harga** (tab Paket dan Bundle) dan menu **Add-on**. Price list sudah terisi dari seeder. Studio tidak menyediakan jasa desain: harga untuk mengoding aset desain jadi milik klien menjadi widget. Di form order, klien memilih apakah asetnya sudah siap atau sebagian masih dibuat. Kalau semua aset wajib siap, hapus opsi `partial` di `design_options` (`config/studio.php`). Layanan yang ditandai "Tonjolkan di beranda" tampil di halaman utama.
   - Add-on bisa berupa harga tetap, persentase dari harga paket (dibulatkan ke Rp1.000 / $1), atau gratis. Isi "Jumlah maksimal" lebih dari 1 untuk add-on per satuan seperti revisi tambahan.
   - Estimasi total di form order dihitung ulang oleh server saat order disimpan, lalu tampil di detail order bersama add-on yang dipilih.
   - Diskon partner (studio rekanan, biasanya 10–15%) diisi di detail order dan tidak tampil ke klien.
3. Tambahkan karya di menu **Portofolio**. Video demo MP4/WebM berdurasi 10 sampai 15 detik paling cocok, dan karya yang ditandai "Tonjolkan di beranda" akan tampil di halaman utama.
4. Cek bagian **FAQ** dan **Kebijakan privasi** di Pengaturan studio. Isi awalnya hanya memakai info yang sudah ada di website. Sesuaikan dengan kenyataan, misalnya hapus baris Discord di kebijakan privasi kalau webhook tidak dipakai, atau tambahkan lama penyimpanan data. Jawaban FAQ soal pembayaran menyebut DP 50%, jadi ubah juga kalau persentase DP diganti. FAQ tampil di beranda dan halaman layanan, dan disembunyikan kalau daftarnya dikosongkan.
5. Teks yang diisi dari admin (tagline, deskripsi layanan, TOS, privasi, FAQ) tampil apa adanya di kedua bahasa. Karena pasarnya juga luar negeri, teks tersebut sebaiknya ditulis dalam bahasa Inggris.

## Deploy ke VPS (SumoPod)

Contoh berikut memakai Ubuntu, Nginx, PHP-FPM 8.3, dan MySQL. SQLite juga bisa dipakai untuk trafik kecil.

1. Pasang paket yang dibutuhkan:

    ```bash
    sudo apt install nginx php8.3-fpm php8.3-{cli,mysql,sqlite3,mbstring,xml,curl,zip,intl,gd,bcmath} unzip
    ```

2. Upload project ke `/var/www/pixelwire-studio` (lewat `git clone` atau scp), lalu jalankan:

    ```bash
    cd /var/www/pixelwire-studio
    composer install --no-dev --optimize-autoloader
    cp .env.example .env
    php artisan key:generate
    ```

3. Ubah `.env` untuk production:

    ```env
    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://domain-kamu.com
    APP_TIMEZONE=Asia/Jakarta
    LOG_LEVEL=warning
    SESSION_SECURE_COOKIE=true

    ADMIN_EMAIL=email-login-admin@gmail.com
    ADMIN_PASSWORD=password-panjang-dan-acak

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=pixelwire
    DB_USERNAME=pixelwire
    DB_PASSWORD=password-database

    MAIL_MAILER=smtp
    MAIL_HOST=smtp.gmail.com
    MAIL_PORT=587
    MAIL_USERNAME=email@gmail.com
    MAIL_PASSWORD=app-password-gmail
    MAIL_FROM_ADDRESS=email@gmail.com
    ```

    Untuk Gmail, gunakan App Password (Google Account, Security, App passwords), bukan password akun biasa.

    `ADMIN_PASSWORD` wajib minimal 12 karakter. Kalau masih `ganti-password-ini` atau terlalu pendek, seeder akan berhenti di production. Sebaiknya email login admin berbeda dari email kontak yang tampil di website. Setelah `migrate --seed` selesai, baris `ADMIN_PASSWORD` boleh dihapus dari `.env`. Seeder akan melewati akun admin kalau baris itu kosong.

4. Siapkan database dan aset:

    ```bash
    php artisan migrate --seed --force
    php artisan storage:link
    npm ci && npm run build
    php artisan optimize
    php artisan filament:optimize
    sudo chown -R www-data:www-data storage bootstrap/cache
    ```

5. Konfigurasi Nginx (`/etc/nginx/sites-available/pixelwire`):

    ```nginx
    server {
        listen 80;
        server_name domain-kamu.com;
        root /var/www/pixelwire-studio/public;
        index index.php;
        client_max_body_size 64M;

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        # File hasil upload tidak boleh dijalankan sebagai PHP.
        location ~* ^/storage/.*\.php$ {
            deny all;
        }

        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        }

        location ~ /\.(?!well-known) {
            deny all;
        }
    }
    ```

    Aktifkan dengan `sudo ln -s /etc/nginx/sites-available/pixelwire /etc/nginx/sites-enabled/`, lalu `sudo nginx -t && sudo systemctl reload nginx`. Pasang HTTPS dengan `sudo certbot --nginx -d domain-kamu.com`.

    Setelah HTTPS jalan, tambahkan baris berikut di blok `server` yang `listen 443`, lalu reload Nginx. Baris ini membuat browser selalu memakai HTTPS. Header keamanan lain (X-Frame-Options, nosniff, Referrer-Policy, dan Content-Security-Policy untuk halaman publik) sudah dikirim oleh aplikasi (`app/Http/Middleware/SecurityHeaders.php`). Kalau nanti menambah script atau embed dari luar (misalnya analytics atau video YouTube), domainnya perlu ditambahkan ke CSP di file itu, karena kalau tidak, browser akan memblokirnya.

    ```nginx
    add_header Strict-Transport-Security "max-age=31536000" always;
    ```

6. Supaya upload video demo sampai 50 MB berhasil, ubah `/etc/php/8.3/fpm/php.ini` menjadi `upload_max_filesize = 64M` dan `post_max_size = 64M`, lalu jalankan `sudo systemctl restart php8.3-fpm`.

7. Keamanan server:

    - Firewall: buka hanya SSH, HTTP, dan HTTPS (`sudo ufw allow OpenSSH && sudo ufw allow 'Nginx Full' && sudo ufw enable`). MySQL cukup diakses dari localhost.
    - Login SSH memakai key, lalu matikan login password dan login root di `/etc/ssh/sshd_config`.
    - Pasang update keamanan otomatis: `sudo apt install unattended-upgrades`.
    - Backup rutin database (`mysqldump`) dan folder `storage/app/public` (video portofolio), simpan salinannya di luar VPS.
    - Kalau situs dipasang di belakang Cloudflare, atur trusted proxies supaya rate limit membaca IP pengunjung asli, bukan IP Cloudflare.
    - Cek dependency secara berkala dengan `composer audit` dan `npm audit`.

Setelah online, daftarkan `https://domain-kamu.com/sitemap.xml` di Google Search Console. Sitemap dan `robots.txt` dibuat otomatis oleh aplikasi dari `APP_URL` (tidak ada file `public/robots.txt`).

Setiap kali ada update kode, jalankan `composer install --no-dev`, `php artisan migrate --force`, `npm run build`, dan `php artisan optimize` di server.

## Struktur penting

- `config/studio.php`: kategori, platform, status order, status yang memakai slot, dan nilai default pengaturan.
- `app/Filament/`: panel admin (resource order, layanan dan bundle, add-on, portofolio, halaman pengaturan, widget statistik).
- `app/Notifications/OrderStatusUpdated.php`: email ke klien saat status order diubah di admin (bisa dimatikan per order lewat toggle di detail order). Email dikirim dalam bahasa yang dipakai klien saat order.
- `public/og-image.png`: gambar preview saat link website dibagikan (X, Discord, WhatsApp). Sumbernya `resources/og/og-image.html`; cara membuat ulang ada di komentar file itu. URL gambar memakai `APP_URL`, jadi preview baru muncul setelah situs online dengan `APP_URL` berisi domain asli.
- `app/Support/PriceEstimator.php`: hitungan estimasi total (paket/bundle + add-on). Aturan yang sama dipakai di `resources/js/app.js` untuk estimasi langsung di form order.
- `app/Http/Controllers/`: halaman publik dan alur order.
- `app/Support/Studio.php`: logika slot dan status commission.
- `app/Support/OrderNotifier.php`: email dan notifikasi Discord untuk order baru.
- `resources/views/`: tampilan Blade halaman publik.
- `resources/views/errors/`: halaman error 404, 419, 429, 500, dan 503. Layout-nya tidak membaca database, jadi tetap tampil saat database bermasalah.
- `resources/views/partials/faq.blade.php`: daftar FAQ yang bisa dibuka-tutup.
- `resources/css/app.css`: warna, font, dan komponen tampilan bergaya manga (outline tebal, stiker, speech bubble).
- `tests/Feature/`: tes otomatis halaman publik, alur order, panel admin, halaman info (privasi, FAQ, sitemap, halaman error).
- `resources/js/app.js`: demo chat box di beranda dan autoplay video portofolio.
- `lang/id.json` dan `lang/id/validation.php`: terjemahan bahasa Indonesia.
