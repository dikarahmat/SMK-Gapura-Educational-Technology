# 🏫 Sistem Presensi SMP PGRI Parung Panjang

> **Proyek Pengabdian kepada Masyarakat (PKM)** Tahun Akademik 2026/2027 Ganjil
> **Perancangan Sistem Informasi Monitoring Presensi Siswa Berbasis QR Code Secara Realtime Guna Meningkatkan Kehadiran Siswa di SMP PGRI Parungpanjang**
> Program Studi Teknik Informatika S-1, Fakultas Ilmu Komputer, Universitas Pamulang

Sistem informasi presensi siswa dan guru berbasis web untuk SMP PGRI Parung Panjang. Presensi dilakukan dengan scan kartu QR di gerbang sekolah, lalu datanya otomatis masuk ke rekap yang bisa dicetak atau diunduh dalam bentuk Excel dan PDF.

Tidak ada pendaftaran akun publik. Semua akun login dibuat oleh Admin lewat halaman **Kelola Akun**.

## ✨ Fitur Unggulan

### 🔐 Multi-Role (3 Role)
- **Admin:** akses penuh ke semua modul (CRUD data, pengaturan sistem, kelola akun, rekap global).
- **Guru:** scan QR dan mode gerbang, serta melihat data siswa, kelas, kehadiran, dan rekap (read-only).
- **Guru Piket:** scan QR dan mode gerbang, presensi guru, serta melihat kehadiran.
- Login memakai **username** (bukan email). Akun bisa dinonaktifkan oleh Admin kapan saja.

### 📷 Presensi Siswa
- Scan kartu QR lewat kamera, atau input manual NISN.
- **Mode Gerbang (Kiosk)** untuk satpam atau petugas gerbang.
- **Anti-Double Scan:** satu siswa hanya tercatat satu kali per hari.
- Status presensi: Hadir, Terlambat, Sakit, Izin, Alfa. Status bisa diubah manual lengkap dengan catatan dan bukti surat.
- Jam masuk, batas terlambat, dan jam pulang bisa diatur lewat Jadwal Operasional.

### 👩‍🏫 Presensi Guru
- Pencatatan kehadiran guru (Hadir / Tidak Hadir) oleh Admin atau Guru Piket.
- Rekap kehadiran guru bisa diekspor ke Excel dan PDF.

### 📊 Rekap & Laporan
- Rekap siswa per hari, minggu, bulan, atau tahun ajaran, dengan filter kelas dan status.
- Cetak rekap lewat modal cetak, atau unduh sebagai **Excel** dan **PDF**.
- Export per kelas atau per siswa, termasuk ZIP untuk semua kelas.
- Grafik kehadiran di dashboard.

### 🪪 Kartu QR Siswa & Guru
- Generate kartu presensi (ID card) dengan QR code.
- Cetak satuan atau massal dalam satu file ZIP.

### 📥 Import & Export Excel
- Import data **Siswa, Guru, Kelas, dan Hari Libur** dari file Excel.
- Template Excel bisa diunduh langsung di tiap halaman import.
- Validasi otomatis untuk data ganda (NISN, NIP).

### 🎨 UI/UX
- Tampilan responsif di HP, tablet, dan komputer.
- Dark mode dan pilihan bahasa (Indonesia / Inggris).
- Tempat Sampah untuk siswa, guru, dan kelas (bisa dipulihkan sebelum dihapus permanen).
- Master data Tahun Ajaran, Hari Libur, dan identitas sekolah.

---

## 🔑 Hak Akses per Role

`✓` = bisa tambah/ubah/hapus, `👁` = hanya lihat (read-only), `✗` = tidak bisa diakses.

| Modul | Admin | Guru | Guru Piket |
| :--- | :---: | :---: | :---: |
| Dashboard | ✓ | 👁 | 👁 |
| Presensi Siswa (scan & mode gerbang) | ✓ | ✓ | ✓ |
| Ubah status presensi siswa | ✓ | ✗ | ✓ |
| Presensi Guru | ✓ | ✗ | ✓ |
| Kehadiran Siswa & Guru | 👁 | 👁 | 👁 |
| Rekap Siswa | ✓ | 👁 | ✗ |
| Rekap Guru | ✓ | ✗ | ✗ |
| Data Siswa | ✓ | 👁 | ✗ |
| Data Guru | ✓ | ✗ | ✗ |
| Data Kelas | ✓ | 👁 | ✗ |
| Pengaturan Sistem | ✓ | ✗ | ✗ |
| Kelola Akun | ✓ | ✗ | ✗ |

---

## 🛠️ Tech Stack

| Komponen | Keterangan |
| :--- | :--- |
| Framework | Laravel 12 / PHP 8.2+ |
| Database | MySQL |
| Frontend | Blade, Tailwind CSS 4, Vite |
| Excel | `maatwebsite/excel` |
| PDF | `barryvdh/laravel-dompdf` |
| QR Code | `simplesoftwareio/simple-qrcode` |

---

## 💻 Cara Install & Menjalankan di Komputer Lokal

### 1. Persyaratan Sistem

Pastikan komputer sudah terinstall:

- **PHP** minimal 8.2, dengan ekstensi `gd`, `zip`, dan `fileinfo` aktif
- **Composer**
- **Node.js & npm**
- **MySQL Server** (bisa pakai XAMPP, Laragon, dsb)
- **Git**

### 2. Langkah Instalasi

**A. Download Repositori**

Buka terminal / CMD, lalu jalankan:
```bash
git clone https://github.com/dikarahmat/sistempresensi.git
cd sistempresensi
```

**B. Install Dependensi**

Install library PHP dan library frontend:
```bash
composer install
npm install
```

**C. Buat Database Kosong**

Buka phpMyAdmin (atau MySQL CLI), lalu buat database kosong dengan nama `sistem_presensi_db`.

**D. Konfigurasi Environment**

Salin file konfigurasi bawaan.

Linux / macOS / Git Bash:
```bash
cp .env.example .env
```
Windows (CMD):
```cmd
copy .env.example .env
```

Buka file `.env` di teks editor, lalu sesuaikan bagian berikut:
```env
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistem_presensi_db
DB_USERNAME=root
DB_PASSWORD=
```

| Variabel | Penjelasan |
| :--- | :--- |
| `APP_URL` | Alamat aplikasi. Untuk lokal biasanya `http://127.0.0.1:8000`. |
| `APP_TIMEZONE` | Zona waktu. Penting supaya jam presensi dan status terlambat akurat. |
| `DB_DATABASE` | Nama database yang tadi dibuat. |
| `DB_USERNAME` / `DB_PASSWORD` | Akun MySQL. Bawaan XAMPP/Laragon: `root` dengan password kosong. |

*(Opsional)* Kamu juga bisa mengatur password akun default sebelum seeding lewat `ADMIN_PASSWORD`, `GURU_PASSWORD`, dan `PIKET_PASSWORD`. Kalau dikosongkan, dipakai password bawaan di tabel Akun Default di bawah.

**E. Generate Key**

```bash
php artisan key:generate
```

**F. Migrasi Database & Isi Data Awal (Seeding)**

Perintah ini membuat semua tabel dan akun default:
```bash
php artisan migrate --seed
```

**G. Hubungkan Storage**

Supaya file hasil unggahan dan kartu bisa diakses dari browser:
```bash
php artisan storage:link
```

**H. Build Aset Frontend**

```bash
npm run build
```
*(Kalau sedang mengembangkan tampilan, pakai `npm run dev` supaya perubahan langsung terlihat.)*

### 3. Cara Menjalankan Aplikasi

```bash
php artisan serve
```

Buka browser dan akses: **http://127.0.0.1:8000**

> **Catatan:** jika di `.env` kamu memakai `QUEUE_CONNECTION=database`, buka terminal kedua dan jalankan `php artisan queue:work` agar proses antrian (misalnya unduhan) tetap berjalan.
> Alternatif untuk development: `composer run dev` menjalankan server, queue, dan Vite sekaligus dalam satu perintah.

---

## 🔑 Akun Default (Login)

Login memakai **USERNAME** (bukan email). Setelah menjalankan `migrate --seed`, gunakan akun berikut:

| Role | Username | Password |
| :--- | :--- | :--- |
| **Admin** | admin | admin123 |
| **Guru** | guru | guru123 |
| **Guru Piket** | piket | piket123 |

Password bisa diganti lewat halaman **Kelola Akun** (`/admin/kelola-akun`) setelah login sebagai Admin.

> ⚠️ **Wajib ganti password default** sebelum aplikasi dipakai di server production.

---

## 📖 Alur Penggunaan Singkat

**Admin (setup awal)**
1. Login, lalu buka **Pengaturan**. Pastikan Tahun Ajaran aktif dan Jadwal Operasional (jam masuk, batas terlambat, jam pulang) sudah benar.
2. Tambah data **Kelas**, **Guru**, dan **Siswa** (manual atau import Excel).
3. Buat akun login untuk guru dan piket di **Kelola Akun**.
4. Cetak kartu QR siswa dan bagikan.

**Harian (Guru / Guru Piket / Admin)**
1. Login, lalu buka **Presensi** atau **Mode Gerbang**.
2. Scan kartu QR siswa saat datang dan pulang.
3. Guru Piket mencatat kehadiran guru di **Presensi Guru**.
4. Admin atau Guru membuka **Rekap**, lalu mencetak atau mengunduh laporan.

---

## 📥 Format Import Excel

Baris pertama file Excel harus berisi **nama kolom (header)**. Template bisa diunduh di setiap halaman import.

**Siswa**

| Kolom | Wajib | Keterangan |
| :--- | :---: | :--- |
| `nisn` | Tidak | 10 digit angka. Kalau diisi, tidak boleh sama dengan siswa lain. |
| `nama` | Ya | Nama lengkap siswa. |
| `kelas` | Ya | Contoh: `7A`. Dibuat otomatis kalau belum ada. |
| `jenis_kelamin` | Tidak | `Laki-laki` atau `Perempuan`. |

**Guru**

| Kolom | Wajib | Keterangan |
| :--- | :---: | :--- |
| `nip` | Tidak | 16 atau 18 digit angka, harus unik. |
| `nama_lengkap` | Ya | Nama lengkap guru. |
| `jenis_kelamin` | Ya | `Laki-laki` atau `Perempuan`. |
| `no_telepon` | Ya | 10 sampai 15 digit angka. |

**Kelas**

| Kolom | Wajib | Keterangan |
| :--- | :---: | :--- |
| `nama` | Ya | Contoh: `7A`, `8B`. Harus diawali tingkat (7/8/9). |
| `tingkat` | Tidak | 7, 8, atau 9. |
| `wali_kelas` | Tidak | Nama atau NIP guru. |

> Aktifkan **Tahun Ajaran** di Pengaturan sebelum import kelas.

---

## 🩺 Masalah Umum

| Masalah | Solusi |
| :--- | :--- |
| Tampilan berantakan / CSS tidak muncul | Jalankan `npm run build`. |
| Error `Class not found` setelah update | Jalankan `composer dump-autoload` lalu `php artisan config:clear`. |
| Gagal konek database | Cek `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` di `.env`, dan pastikan MySQL sudah menyala. |
| Kamera scanner tidak muncul | Izinkan akses kamera di browser. Di server online, scanner butuh **HTTPS**. Bisa juga pakai input manual NISN. |
| Gambar / kartu tidak tampil | Jalankan `php artisan storage:link`. |

---

## 👥 Tim Pelaksana

| Nama | Jabatan |
| :--- | :--- |
| Pahlevy Zaqi Shah | Ketua Pelaksana |
| Salma Maulida Alkhansa | Wakil Ketua |
| Adelya Revalina | Sekretaris & Bendahara |
| Dika Rahmat Fadillah | Developer |
| Muhammad Rayya Ali Syakir | Humas & Kemitraan |
| Vina Aulia | Humas & Kemitraan |
| Ahmad Sadadi Tarigan | Publikasi & Press Release |
| Devania Aurella Ramadhani | Publikasi & Press Release |
| Risma Yanti | Media & Dokumentasi |

---
*Developed with ❤️*