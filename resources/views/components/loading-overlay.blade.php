{{-- Progress bar tipis di atas layar: indikator utama saat PINDAH HALAMAN (klik tautan/menu).
     Overlay gelap + spinner di bawah hanya dipakai saat SUBMIT FORM (simpan/hapus/import). --}}
<div id="smart-top-bar" aria-hidden="true"></div>

<div id="smart-page-loader" class="loader-overlay" style="display: none;">
    <div class="loader"></div>
</div>

<style>
    #smart-top-bar {
        position: fixed;
        top: env(safe-area-inset-top, 0px);
        left: 0;
        right: 0;
        height: 3px;
        z-index: 999999998;
        pointer-events: none;
        background: var(--color-primary);
        box-shadow: none;
        transform-origin: left center;
        transform: scaleX(0);
        opacity: 0;
        transition: transform .25s ease-out, opacity .3s ease;
    }
    #smart-top-bar.is-active { opacity: 1; }

    /* Desktop (>= 1024px) memakai spinner overlay (bawah); progress bar hanya untuk HP/tablet. */
    @media (min-width: 1024px) {
        #smart-top-bar { display: none !important; }
    }

    /* Overlay loading penuh layar: HITAM transparan (alpha 0.50) sehingga halaman
       di belakang masih samar terlihat. Warna overlay INI TIDAK DIUBAH - tetap
       gelap supaya spinner putih di atasnya tetap kontras & terbaca. */
    .loader-overlay {
        position: fixed;
        inset: 0;
        width: 100vw;
        height: 100vh;
        height: 100dvh;
        z-index: 999999999 !important;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.50);
        pointer-events: auto;
        opacity: 1;
        transition: opacity 250ms ease;
    }

    .loader-overlay.is-hiding {
        opacity: 0;
    }

    /* Cincin loading halaman (satu CSS bersama untuk SEMUA layout: admin,
       guru, dan gerbang semuanya lewat satu include di layouts/app.blade.php).
       WARNA: PUTIH (#FFFFFF), bukan biru tema, karena latarnya overlay gelap
       transparan (rgba(0,0,0,.50)) di atas - spinner putih selalu terbaca
       di semua kondisi tampilan.
       - `background`      : warna cincin (putih).
       - Jejak (track)     : conic gradient mulai transparan, naik ke 25%
                             (#00000040 = hitam alpha 25%) sebagai sisa
                             jejak tipis, lalu ke opaque penuh untuk kepala
                             spinner.
       Ukuran (50px), rasio, dan kecepatan animasi (l3 1s linear) TIDAK
       berubah - hanya warna yang diganti. */
    .loader {
        width: 50px;
        padding: 8px;
        aspect-ratio: 1;
        border-radius: 50%;
        background: #ffffff;                  /* spinner putih            */
        --_m:
            conic-gradient(#0000 10%, #00000040 25%, #000),
            linear-gradient(#000 0 0) content-box;
        -webkit-mask: var(--_m);
                mask: var(--_m);
        -webkit-mask-composite: source-out;
                mask-composite: subtract;
        animation: l3 1s infinite linear;
    }
    @keyframes l3 { to { transform: rotate(1turn); } }

    /* Reduced motion: animasi diperlambat sangat pelan, indikator TETAP tampil. */
    @media (prefers-reduced-motion: reduce) {
        .loader {
            animation-duration: 5s;
        }
    }
</style>

<script>
(function() {
    'use strict';

    let loaderTimeout = null;
    let fadeTimeout = null;
    let safetyTimeout = null;
    const DELAY_MS = 100;
    const FADE_MS = 250;
    // Jaringan/macet total: paksa sembunyikan agar overlay tidak pernah menutupi halaman.
    const SAFETY_MS = 15000;

    function getLoader() {
        return document.getElementById('smart-page-loader');
    }

    function showLoaderWithDelay() {
        if (loaderTimeout) {
            clearTimeout(loaderTimeout);
        }
        loaderTimeout = setTimeout(function() {
            const loader = getLoader();
            if (loader) {
                if (fadeTimeout) {
                    clearTimeout(fadeTimeout);
                    fadeTimeout = null;
                }
                loader.classList.remove('is-hiding');
                loader.style.display = 'flex';

                if (safetyTimeout) {
                    clearTimeout(safetyTimeout);
                }
                safetyTimeout = setTimeout(hideLoader, SAFETY_MS);
            }
        }, DELAY_MS);
    }

    function hideLoader() {
        if (loaderTimeout) {
            clearTimeout(loaderTimeout);
            loaderTimeout = null;
        }
        if (safetyTimeout) {
            clearTimeout(safetyTimeout);
            safetyTimeout = null;
        }
        const loader = getLoader();
        if (!loader || loader.style.display === 'none') {
            return;
        }
        // Fade out halus 250ms sebelum overlay dilepas.
        loader.classList.add('is-hiding');
        if (fadeTimeout) {
            clearTimeout(fadeTimeout);
        }
        fadeTimeout = setTimeout(function() {
            loader.style.display = 'none';
            loader.classList.remove('is-hiding');
            fadeTimeout = null;
        }, FADE_MS);
    }

    function isDownloadDestination(href) {
        try {
            if (!href) return false;
            const url = new URL(href, window.location.href);
            const path = url.pathname.toLowerCase();
            const downloadRoute = /(?:^|\/)(?:export|download|template|print-?cards?|generate-qr|import)(?:[-/._]|$)/i.test(path);
            const fileExtension = /\.(?:pdf|xlsx?|csv|zip|docx?|png|jpe?g|webp|svg)(?:$|\/)/i.test(path);
            const downloadParameter = ['download', 'attachment', 'export', 'pdf'].some(function(key) {
                return url.searchParams.has(key);
            });

            return downloadRoute || fileExtension || downloadParameter;
        } catch (error) {
            return false;
        }
    }

    /* Tutup pop-up yang memuat form ini SEBELUM loading tampil, supaya modal
       tidak terlihat "di belakang" overlay loading. Dua jenis pop-up yang
       dipakai di project ini:
         1. <dialog> native (mis. modal Tambah/Edit/Hapus Akun) - berada di
            TOP LAYER browser sehingga SELALU menutupi overlay loading, berapa
            pun z-index overlay. Karena itu WAJIB ditutup, bukan sekadar
            ditimpa.
         2. Bootstrap modal (mis. Tambah Siswa, Import Excel, Cetak Kartu).
       Menutup pop-up TIDAK membatalkan submit: form tetap ada di DOM, hanya
       status "terbuka" yang dilepas. Kalau validasi browser gagal, event
       submit memang tidak pernah sampai ke sini, jadi modal tetap terbuka
       (perilaku yang diinginkan). Dialog SweetAlert tidak perlu ditangani di
       sini karena pop-up-nya sudah menutup sendiri sebelum form tersembunyi
       di-submit. */
    function closeContainingPopup(form) {
        if (!(form instanceof Element)) {
            return;
        }

        const dialog = form.closest('dialog');
        if (dialog && dialog.open) {
            try {
                dialog.close();
            } catch (error) { /* gagal menutup: lanjut ke loading + submit */ }
            return;
        }

        const modalEl = form.closest('.modal');
        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
            try {
                const instance = window.bootstrap.Modal.getInstance(modalEl);
                if (instance) {
                    instance.hide();
                }
            } catch (error) { /* gagal menutup: lanjut ke loading + submit */ }
        }
    }

    /* ======================================================================
       PROGRESS BAR PINDAH HALAMAN
       - Mulai SEGERA saat tautan diketuk (tanpa jeda 100ms), naik cepat ke ~30%,
         lalu merayap pelan ke maks ~90% selama halaman tujuan dimuat.
       - Posisi bar disimpan saat halaman ditinggalkan; halaman baru MELANJUTKAN
         dari posisi itu (tidak reset ke 0), lalu menuntaskannya ke 100%.
       - Waktu tampil minimum (MIN_MS) dihitung sejak ketukan, jadi tidak sekadar
         kelip bila server cepat.
       ====================================================================== */
    // Desktop = spinner overlay; HP/tablet = progress bar di atas. Batas sama dengan navbar bawah (1024px).
    function isDesktop() {
        return window.matchMedia && window.matchMedia('(min-width: 1024px)').matches;
    }

    const BAR_KEY = '__topBarState';
    const BAR_MIN_MS = 350;
    const BAR_MAX_P = 0.9;
    const BAR_SAFETY_MS = 15000;
    let barEl = null, barP = 0, barTick = null, barStartedAt = 0;
    let barFinish = null, barReset = null, barSafety = null, barActive = false;

    function getBar() {
        if (!barEl) barEl = document.getElementById('smart-top-bar');
        return barEl;
    }

    function setBar(v, animate) {
        const bar = getBar();
        if (!bar) return;
        barP = v;
        if (!animate) bar.style.transition = 'none';
        bar.style.transform = 'scaleX(' + v + ')';
        if (!animate) {
            void bar.offsetWidth;
            bar.style.transition = '';
        }
    }

    function barStart(fromP, startedAt) {
        const bar = getBar();
        if (!bar) return;
        clearTimeout(barFinish); barFinish = null;
        clearTimeout(barReset); barReset = null;
        clearInterval(barTick);
        clearTimeout(barSafety);

        barActive = true;
        barStartedAt = startedAt || Date.now();
        bar.classList.add('is-active');
        setBar(typeof fromP === 'number' ? fromP : 0, false);

        if (typeof fromP !== 'number') {
            setTimeout(function () { if (barActive) setBar(0.3, true); }, 20);
        }
        barTick = setInterval(function () {
            if (barP < BAR_MAX_P) setBar(barP + (BAR_MAX_P - barP) * 0.07, true);
        }, 300);
        barSafety = setTimeout(barDone, BAR_SAFETY_MS);
    }

    function barDone() {
        const bar = getBar();
        if (!bar || !barActive) return;
        clearInterval(barTick); barTick = null;
        clearTimeout(barSafety); barSafety = null;
        const wait = Math.max(0, BAR_MIN_MS - (Date.now() - barStartedAt));
        clearTimeout(barFinish);
        barFinish = setTimeout(function () {
            setBar(1, true);
            barReset = setTimeout(function () {
                bar.classList.remove('is-active');       // fade out
                barReset = setTimeout(function () {
                    barActive = false;
                    setBar(0, false);
                    barReset = null;
                }, 320);
            }, 220);
            barFinish = null;
        }, wait);
    }

    // Simpan posisi bar saat meninggalkan halaman supaya halaman baru bisa melanjutkan.
    window.addEventListener('pagehide', function () {
        if (!barActive) return;
        try {
            sessionStorage.setItem(BAR_KEY, JSON.stringify({ p: barP, t0: barStartedAt, w: Date.now() }));
        } catch (error) { /* abaikan */ }
    });

    // Halaman baru: lanjutkan bar bila datang dari klik tautan, lalu tuntaskan saat siap.
    (function resumeBar() {
        try {
            const raw = sessionStorage.getItem(BAR_KEY);
            sessionStorage.removeItem(BAR_KEY);
            if (raw) {
                const o = JSON.parse(raw);
                if (o && Date.now() - o.w < 10000) {
                    barStart(Math.min(Math.max(o.p || 0.3, 0.1), BAR_MAX_P), o.t0);
                }
            }
        } catch (error) { /* abaikan */ }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', barDone);
        } else {
            barDone();
        }
    })();

    window.addEventListener('load', hideLoader);
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            hideLoader();
            barDone();
        }
    });

    document.addEventListener('click', function(event) {
        const target = event.target;
        const anchor = target instanceof Element ? target.closest('a') : null;
        if (!anchor) return;

        const href = anchor.getAttribute('href');
        const linkTarget = anchor.getAttribute('target');
        const normalizedHref = href ? href.trim().toLowerCase() : '';

        if (!normalizedHref || normalizedHref.includes('#') || normalizedHref.startsWith('javascript:') || linkTarget === '_blank') {
            return;
        }

        if (anchor.hasAttribute('download') || anchor.hasAttribute('data-no-loader') || anchor.hasAttribute('data-download') || anchor.hasAttribute('data-import') || anchor.closest('[data-download]') || anchor.closest('[data-import]') || isDownloadDestination(href) || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
            return;
        }

        const sidebar = anchor.closest('.app-sidebar-drawer');
        if (sidebar) {
            try {
                const sidebarState = window.Alpine && window.Alpine.$data(document.body);
                if (sidebarState && typeof sidebarState.sidebarOpen !== 'undefined') {
                    sidebarState.sidebarOpen = false;
                } else {
                    sidebar.classList.remove('mobile-sidebar-active');
                }
            } catch (error) {
                sidebar.classList.remove('mobile-sidebar-active');
            }
        }

        // Pindah halaman: desktop = spinner overlay, HP/tablet = progress bar tipis.
        // Bila ada skrip lain yang membatalkan klik (preventDefault), tidak ada
        // perpindahan halaman, jadi bar tidak dimulai.
        if (event.defaultPrevented) return;
        if (isDesktop()) {
            showLoaderWithDelay();   // desktop: spinner muter
        } else {
            barStart();              // HP/tablet: garis di atas
        }
    });

    document.addEventListener('submit', function(event) {
        const form = event.target;
        const submitter = event.submitter;
        const action = form instanceof HTMLFormElement ? (form.getAttribute('action') || window.location.href) : '';
        if (form instanceof HTMLFormElement) {
            if (form.hasAttribute('download') || form.hasAttribute('data-no-loader') || form.hasAttribute('data-download') || form.hasAttribute('data-import-form') || form.hasAttribute('data-import') || isDownloadDestination(action)) {
                return;
            }
            if (submitter && (submitter.hasAttribute('download') || submitter.hasAttribute('data-no-loader') || submitter.hasAttribute('data-download') || submitter.hasAttribute('data-import') || submitter.closest('[data-download]') || submitter.closest('[data-import]') || isDownloadDestination(submitter.getAttribute('formaction')))) {
                return;
            }
        }
        // Tutup modal/dialog form lebih dulu agar spinner langsung terlihat.
        closeContainingPopup(form);
        showLoaderWithDelay();
    });

    // Desktop: spinner tetap tampil selama halaman baru dimuat (perilaku lama).
    // HP/tablet: kelanjutannya ditangani progress bar (resumeBar di atas).
    if (isDesktop() && document.readyState !== 'complete') {
        showLoaderWithDelay();
    }

    window.showSmartLoader = showLoaderWithDelay;
    window.hideSmartLoader = hideLoader;
})();
</script>