@php
    use App\Support\Theme;
    $theme = new Theme(config('theme', []));
    $vars = $theme->cssVariables();
@endphp

<style>
    /* ========================================================================
       SISTEM TEMA WARNA TERPUSAT — SIMPRES-QR
       Satu source of truth untuk seluruh warna aplikasi.
       Ubah config/theme.php baris 'primary', lalu jalankan
       php artisan optimize:clear.
       Blok ini WAJIB ditempatkan di <head> SEBELUM CSS lain (Bootstrap,
       Tailwind, app.css, presensi-tokens.css) supaya variabel siap dipakai.
       ======================================================================== */
    :root {
        @foreach ($vars as $name => $value)
            {{ $name }}: {{ $value }};
        @endforeach
    }

    /* Tailwind CDN / utility mapping: map class warna yang dipakai ke variabel tema. */
    @if (config('theme.map_tailwind', true))
        .text-primary { color: var(--color-primary) !important; }
        .bg-primary { background-color: var(--color-primary) !important; }
        .border-primary { border-color: var(--color-primary) !important; }
        .text-primary-hover:hover { color: var(--color-primary-hover) !important; }
        .bg-primary-hover:hover { background-color: var(--color-primary-hover) !important; }
        .text-secondary { color: var(--color-secondary) !important; }
        .bg-secondary { background-color: var(--color-secondary) !important; }
        .text-accent { color: var(--color-accent) !important; }
        .bg-accent { background-color: var(--color-accent) !important; }
    @endif

    /* Bootstrap 5 mapping: --bs-primary dan --bs-primary-rgb. */
    @if (config('theme.map_bootstrap', true))
        :root {
            --bs-primary: var(--color-primary);
            --bs-primary-rgb: var(--color-primary-rgb);
            --bs-secondary: var(--color-secondary);
            --bs-accent: var(--color-accent);
        }
        .btn-primary {
            background-color: var(--color-primary) !important;
            border-color: var(--color-primary) !important;
        }
        .btn-primary:hover {
            background-color: var(--color-primary-hover) !important;
            border-color: var(--color-primary-hover) !important;
        }
        .btn-outline-primary {
            color: var(--color-primary) !important;
            border-color: var(--color-primary) !important;
        }
        .btn-outline-primary:hover {
            background-color: var(--color-primary) !important;
            border-color: var(--color-primary) !important;
            color: #ffffff !important;
        }
        .text-primary { color: var(--color-primary) !important; }
        .bg-primary { background-color: var(--color-primary) !important; }
        .border-primary { border-color: var(--color-primary) !important; }

        /* Link biru (daftar, navbar) juga ikut tema. */
        .text-primary a,
        a.text-primary {
            color: var(--color-primary) !important;
        }
        a.text-primary:hover {
            color: var(--color-primary-hover) !important;
        }
    @endif

    /* Warna aksi (Excel/PDF) kini dihasilkan dari config('theme.action')
       oleh Theme::cssVariables() di atas (variabel --action-*). */
</style>
