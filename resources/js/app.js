const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/*
 * Demo chat box di hero: pesan masuk bergiliran seperti chat Twitch,
 * dengan warna frame berbeda untuk tiap role, plus alert sesekali.
 */
const roles = {
    broadcaster: { color: '#ff7ab2', badge: 'B' },
    mod: { color: '#6fe3b5', badge: 'M' },
    vip: { color: '#ffe066', badge: 'V' },
    sub: { color: '#82cdff', badge: 'S' },
    viewer: { color: '#e9e3f2', badge: '' },
};

const script = [
    ['viewer', 'mochi_bun', 'first time here, the chat box is so cute'],
    ['sub', 'kopi_susu', 'the frames change per role??'],
    ['mod', 'nightowl_mod', 'reminder: be nice in chat'],
    ['vip', 'pixel_peach', 'is this coded or a plugin'],
    ['broadcaster', 'you', 'coded, works in OBS and StreamElements'],
    ['viewer', 'lurker_404', 'how do the emotes fit like that'],
    ['sub', 'tahu_bulat', 'need this for my vtuber debut'],
    ['viewer', 'raincloud', 'the slide-in animation is smooth'],
    ['mod', 'nightowl_mod', '!commission for the link'],
    ['vip', 'pixel_peach', 'ok I am ordering one'],
];

const alerts = ['tahu_bulat just subscribed!', 'pixel_peach raided with 24 viewers!', 'raincloud just followed!'];

function messageNode([role, name, text]) {
    const r = roles[role];
    const el = document.createElement('div');
    el.className = 'demo-msg';
    el.style.setProperty('--role', r.color);
    if (r.badge) {
        const badge = document.createElement('span');
        badge.className = 'badge';
        badge.setAttribute('aria-hidden', 'true');
        badge.textContent = r.badge;
        el.append(badge);
    }
    const body = document.createElement('div');
    const who = document.createElement('span');
    who.className = 'who';
    who.textContent = name;
    const msg = document.createElement('span');
    msg.textContent = text;
    body.append(who, msg);
    el.append(body);
    return el;
}

function startChatDemo(root) {
    const chat = root.querySelector('[data-demo-chat]');
    const stage = root.querySelector('[data-demo-stage]');
    if (!chat) return;

    if (reduceMotion) {
        script.slice(0, 5).forEach((m) => chat.append(messageNode(m)));
        return;
    }

    let i = 0;
    let tick = 0;

    const step = () => {
        if (document.hidden) return;
        chat.append(messageNode(script[i % script.length]));
        while (chat.children.length > 7) chat.firstElementChild.remove();
        i++;
        tick++;

        if (stage && tick % 5 === 0) {
            const alert = document.createElement('div');
            alert.className = 'demo-alert';
            alert.textContent = alerts[(tick / 5 - 1) % alerts.length];
            stage.append(alert);
            setTimeout(() => alert.remove(), 3300);
        }
    };

    for (let n = 0; n < 3; n++) step();
    setInterval(step, 1900);
}

/*
 * Video portofolio hanya diputar saat terlihat di layar, supaya halaman
 * tetap ringan walaupun berisi banyak demo.
 */
function autoplayVisibleVideos() {
    const videos = document.querySelectorAll('video[data-autoplay]');
    if (!videos.length || reduceMotion || !('IntersectionObserver' in window)) return;

    const io = new IntersectionObserver((entries) => {
        entries.forEach(({ target, isIntersecting }) => {
            if (isIntersecting) {
                target.play().catch(() => {});
            } else {
                target.pause();
            }
        });
    }, { threshold: 0.4 });

    videos.forEach((v) => io.observe(v));
}

/* Field "username" di form order disembunyikan kalau klien memilih email saja. */
function contactHandleToggle() {
    const select = document.querySelector('[name="contact_platform"]');
    const wrap = document.querySelector('[data-handle-wrap]');
    if (!select || !wrap) return;
    const sync = () => {
        wrap.hidden = select.value === 'email';
    };
    select.addEventListener('change', sync);
    sync();
}

/* Tombol kirim dinonaktifkan setelah diklik, supaya klik dua kali tidak mengirim form dua kali. */
function submitOnce() {
    document.querySelectorAll('form[data-submit-once]').forEach((form) => {
        const buttons = [...form.querySelectorAll('button[type="submit"]')].map((b) => [b, b.disabled]);
        form.addEventListener('submit', () => {
            buttons.forEach(([b]) => { b.disabled = true; b.setAttribute('aria-busy', 'true'); });
        });
        // Kalau klien kembali ke halaman ini lewat tombol Back, tombol kembali seperti semula.
        window.addEventListener('pageshow', (e) => {
            if (e.persisted) buttons.forEach(([b, wasDisabled]) => { b.disabled = wasDisabled; b.removeAttribute('aria-busy'); });
        });
    });
}

/* Menu HP tertutup lagi saat klik di luar menu atau menekan Escape. */
function mobileMenu() {
    const menu = document.querySelector('[data-mobile-menu]');
    if (!menu) return;
    document.addEventListener('click', (e) => {
        if (menu.open && !menu.contains(e.target)) menu.open = false;
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menu.open) {
            menu.open = false;
            menu.querySelector('summary').focus();
        }
    });
}

/*
 * Estimasi total di form order: harga paket/bundle + add-on, mengikuti mata uang yang dipilih.
 * Aturannya sama dengan App\Support\PriceEstimator (add-on persen dibulatkan ke Rp1.000 atau $1).
 * Angka ini hanya tampilan; server menghitung ulang saat order disimpan.
 */
function formatMoney(amount, currency) {
    if (currency === 'USD') {
        const digits = Number.isInteger(amount) ? 0 : 2;
        return '$' + amount.toLocaleString('en-US', { minimumFractionDigits: digits, maximumFractionDigits: digits });
    }
    return 'Rp' + Math.round(amount).toLocaleString('id-ID');
}

function roundFor(amount, currency) {
    return currency === 'USD' ? Math.round(amount) : Math.round(amount / 1000) * 1000;
}

function priceEstimate() {
    const box = document.querySelector('[data-estimate]');
    const select = document.querySelector('[data-package-select]');
    if (!box || !select) return;

    const totalEl = box.querySelector('[data-estimate-total]');
    const form = select.form;
    const currency = () => form.querySelector('[name="currency"]:checked')?.value || 'IDR';

    const update = () => {
        const cur = currency();
        const key = cur === 'USD' ? 'usd' : 'idr';

        // Label harga di pilihan paket dan add-on ikut mata uang yang dipilih.
        select.querySelectorAll('option[data-label-idr]').forEach((o) => { o.textContent = o.dataset['label' + (cur === 'USD' ? 'Usd' : 'Idr')]; });
        form.querySelectorAll('[data-currency-label]').forEach((el) => { el.textContent = el.dataset['label' + (cur === 'USD' ? 'Usd' : 'Idr')]; });

        const option = select.selectedOptions[0];
        if (!option || !option.dataset[key]) {
            totalEl.textContent = totalEl.dataset.empty;
            totalEl.dataset.state = 'empty';
            return;
        }

        const base = parseFloat(option.dataset[key]);
        let total = base;
        form.querySelectorAll('[data-addon]:checked').forEach((input) => {
            const qtyInput = input.closest('.group')?.querySelector('[data-addon-qty]');
            const max = parseInt(input.dataset.max, 10) || 1;
            const qty = Math.min(Math.max(parseInt(qtyInput?.value, 10) || 1, 1), max);
            if (input.dataset.type === 'percent') {
                total += roundFor(base * parseInt(input.dataset.percent, 10) / 100, cur) * qty;
            } else if (input.dataset.type === 'fixed') {
                total += parseFloat(input.dataset[key]) * qty;
            }
        });

        const starting = option.dataset.starting === '1';
        totalEl.textContent = (starting ? box.dataset.from + ' ' : '') + formatMoney(total, cur);
        totalEl.dataset.state = 'ready';
    };

    form.addEventListener('change', update);
    form.addEventListener('input', update);
    update();
}

document.querySelectorAll('[data-demo]').forEach(startChatDemo);
autoplayVisibleVideos();
contactHandleToggle();
mobileMenu();
priceEstimate();
submitOnce();
