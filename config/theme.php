<?php

return [

    // ============ WARNA TEMA UTAMA ============
    'primary'   => '#ff00ea',
    'secondary' => '#7e22ce',
    'accent'    => '#06b6d4',

    // ============ STATUS PRESENSI (TETAP) ============
    'status' => [
        'hadir'     => '#059669',
        'terlambat' => '#d97706',
        'sakit'     => '#2563eb',
        'izin'      => '#7e22ce',
        'alfa'      => '#e11d48',
        'libur'     => '#94a3b8',
        'belum'     => '#cbd5e1',
    ],

    // ============ WARNA AKSI (TETAP) ============
    'action' => [
        'excel' => '#059669',
        'pdf'   => '#e11d48',
    ],

    // ============ WARNA TOMBOL PER PERAN ============
    // Nilai yang boleh: 'primary' | 'primary-dark' | 'primary-light' | hex '#rrggbb'
    // 'primary*' = ikut tema. Hex = warna tetap, tidak ikut tema.
    // Dipakai di halaman siswa, guru, kelas, hari libur, akun, tong sampah, dll.
    'buttons' => [
        'print_card' => 'primary',        // Cetak Kartu
        'add'        => '#0041cc',        // Tambah Siswa / Guru / Kelas / Hari Libur / dst (default BIRU, tidak ikut primary)
        'import'     => '#27da16',        // Import Excel
        'delete'     => '#e11d48',        // Hapus
        'refresh'    => '#ff00ea',        // Tombol refresh/reset (abu)
        'gate'       => 'primary',        // Mode Gerbang
        'scanner'    => '#0041cc',   // Buka Scanner QR
        'save'       => 'primary',        // Simpan
    ],

    // ============ IKON PINTASAN CEPAT DI DASHBOARD ============
    // Yang diatur WARNA IKON-nya. Latar kotaknya otomatis tint terang dari warna yang sama.
    'shortcuts' => [
        'presensi'   => 'primary',
        'kehadiran'  => '#059669',
        'rekap'      => '#e11d48',
        'siswa'      => '#06b6d4',
        'guru'       => '#eb00b8',
        'kelas'      => '#f59e0b',
        'libur'      => '#e11d48',
        'pengaturan' => 'primary',
    ],

    // ============ HALAMAN KIOSK / MODE GERBANG ============
    'kiosk' => [
        'stripe' => '#f59e0b',   // garis aksen oranye di header/footer (bukan primary)
    ],

    // ============ PEMETAAN FRAMEWORK ============
    'map_bootstrap' => true,
    'map_tailwind'  => true,
];
