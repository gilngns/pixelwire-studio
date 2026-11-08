<?php

return [

    /*
    | Nama studio dan email admin yang menerima notifikasi order baru.
    */
    'name' => env('STUDIO_NAME', 'Pixelwire Studio'),

    'admin_email' => env('STUDIO_ADMIN_EMAIL', 'pixelwirestudio@gmail.com'),

    /*
    | Akun login panel admin, dibuat oleh `php artisan db:seed`. Dibaca lewat config
    | (bukan env() di seeder) supaya tetap terbaca setelah `php artisan optimize`.
    */
    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    | Discord webhook (opsional). Kalau diisi, setiap order baru juga dikirim
    | ke channel Discord sebagai notifikasi.
    */
    'discord_webhook' => env('STUDIO_DISCORD_WEBHOOK'),

    /*
    | Kategori widget. Key disimpan di database, label ditampilkan di website.
    */
    'categories' => [
        'chatbox' => 'Chat box',
        'alerts' => 'Alerts',
        'timer' => 'Timers & goals',
        'interactive' => 'Interactive',
        'overlay' => 'Overlay scenes',
    ],

    'platforms' => [
        'streamelements' => 'StreamElements',
        'streamlabs' => 'Streamlabs',
        'obs' => 'OBS browser source',
        'other' => 'Other / not sure',
    ],

    'contact_platforms' => [
        'discord' => 'Discord',
        'x' => 'X (Twitter)',
        'instagram' => 'Instagram',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email only',
    ],

    /*
    | Studio tidak menyediakan jasa desain: klien membawa aset desain jadi.
    | Hapus 'partial' kalau semua aset wajib siap sebelum order.
    */
    'design_options' => [
        'yes' => 'My design assets are ready',
        'partial' => 'Some assets are still being made, I will send them before work starts',
    ],

    /*
    | Status order. Status yang ada di "slot_statuses" dihitung memakai slot.
    */
    'order_statuses' => [
        'new' => 'New',
        'quoted' => 'Quote sent',
        'waiting_payment' => 'Waiting for payment',
        'in_progress' => 'In progress',
        'revision' => 'Revision',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'slot_statuses' => ['waiting_payment', 'in_progress', 'revision'],

    /*
    | Nilai default pengaturan studio. Bisa diubah dari admin (menu Studio settings).
    */
    'defaults' => [
        'commission_open' => true,
        'slots_total' => 5,
        'deposit_percent' => 50,
        'reply_days_min' => 1,
        'reply_days_max' => 3,
        'tagline' => 'Hand-coded widgets for streamers and VTubers, built to match your overlay and tested in OBS before delivery.',
        'about' => "Pixelwire Studio builds the moving parts of a stream: chat boxes that match your overlay, alerts with personality, timers, goals, and widgets that react to your chat.\n\nYou bring the design, I turn it into a working widget. Every widget is hand-coded and tested in OBS before delivery.",
        'contact_discord' => '',
        'contact_x' => 'pixelwirestudio',
        'contact_instagram' => 'pixelwirestudio',
        'contact_threads' => 'pixelwirestudio',
        'contact_email' => 'pixelwirestudio@gmail.com',
        // Metode pembayaran untuk publik (halaman layanan). Nomor rekening/e-wallet jangan ditulis di sini.
        'payment_info' => "Indonesia: bank transfer or e-wallet (IDR).\nInternational: PayPal invoice (USD).\n50% down payment before work starts, the rest before final files are sent.",
        // Detail rekening (nomor rekening, e-wallet, email PayPal). Hanya untuk klien yang order-nya sudah dikonfirmasi:
        // dikirim di email "Menunggu pembayaran" dan tampil di halaman cek status. Diisi dari admin.
        'payment_details' => '',
        'terms' => "## Before ordering\n\n- Fill in the order form as clearly as you can. I will reply with a quote within 1 to 3 days.\n- Work starts after the down payment (50%) is received. Your slot is reserved from that point.\n- Design is not included. You provide the finished design assets (images or frames, fonts, sounds), and I code them into the widget.\n\n## Revisions\n\n- Each package includes the number of revisions listed on the services page.\n- A revision is a change to the agreed brief. New features after the brief is agreed are quoted separately.\n\n## Delivery\n\n- You receive the widget code (HTML/CSS/JS and fields) plus a short setup guide for your platform.\n- I can help you install it over Discord screen share if needed.\n\n## Usage rights\n\n- You may use the widget on your own channels for as long as you like.\n- You may not resell or redistribute the code.\n- I may show the work in my portfolio unless you ask me not to before the project starts.\n\n## Refunds\n\n- Full refund if work has not started.\n- After work has started, the down payment is non-refundable.\n- If I cannot finish the project, you get a full refund.",
        'privacy' => "## What I collect\n\n- When you send an order: your name or channel name, email, the contact app and username you chose, your channel link, links to your design files, the brief, budget, deadline, add-ons and the language you used on the site.\n- When you track an order: the order code and email you enter, only to find your order.\n\n## How it is used\n\n- Only to handle your commission: reply to your brief, send the quote and status updates, and deliver the widget.\n- Your details are not sold or shared for advertising.\n\n## Where it goes\n\n- Orders are stored on the server that runs this website.\n- Emails (order confirmation and status updates) are sent through the studio's email provider.\n- New order notifications may also be posted to a private Discord channel used by the studio.\n- Payments happen through bank transfer, e-wallet or PayPal. This website does not collect card or bank details.\n\n## Cookies\n\n- The site only uses the cookies it needs to work: a session cookie (which also remembers your language and currency) and a security token for forms.\n- No analytics or advertising cookies.\n\n## Your data\n\n- To see, correct or delete the details from your order, email pixelwirestudio@gmail.com.",
        // Tanya-jawab di beranda dan halaman layanan. Bisa diubah dari admin (Pengaturan studio, bagian FAQ).
        'faq' => [
            ['question' => 'Which platforms do the widgets work with?', 'answer' => 'StreamElements, Streamlabs and any OBS browser source. Tell me which one you use in the order form.'],
            ['question' => 'What do I receive?', 'answer' => 'The widget code (HTML/CSS/JS and fields) plus a short setup guide for your platform.'],
            ['question' => 'How long does it take?', 'answer' => 'Each package lists its turnaround on the services page. Work starts once the deposit is paid. Need it sooner? Add express delivery in the order form.'],
            ['question' => 'How do I pay?', 'answer' => "Indonesia: bank transfer or e-wallet (IDR). International: PayPal invoice (USD).\n50% down payment before work starts, the rest before final files are sent. The account details are sent once your order is confirmed."],
            ['question' => 'How many revisions are included?', 'answer' => 'Each package lists its revisions on the services page, and extra revisions can be added in the order form. A revision is a change to the agreed brief; new features are quoted separately.'],
            ['question' => 'Do you make the design too?', 'answer' => 'No. I only do the integration: I code your finished design into a working widget. Have the design made first, then send me the files with your brief.'],
            ['question' => 'Can you help me install it?', 'answer' => 'Yes. Installation help over a call is a free add-on, and you always get a setup guide.'],
            ['question' => 'Can I use the widget on more than one channel?', 'answer' => 'You may use the widget on your own channels for as long as you like. Reselling or redistributing the code is not allowed.'],
        ],
    ],

];
