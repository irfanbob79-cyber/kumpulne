# Kumpulne 📁

**Sistem Portal Pengumpulan Tugas Siswa Tanpa Login (Folder Dropzone & Direct-to-Google Drive)**
*Didesain khusus untuk kegiatan belajar mengajar dan pengumpulan karya praktik (DKV) di SMKN 1 Labuhan Maringgai.*

---

## 🚀 Fitur Utama

1. **Portal Siswa (Publik & Tanpa Login):**
   - **Grid Folder Tugas (`/`):** Daftar tugas aktif dalam kartu dropzone interaktif dengan filter live Kelas dan Mata Pelajaran serta pencarian judul.
   - **Tautan Khusus Tugas (`/kumpul/{slug}`):** Area fokus dropzone besar untuk link langsung yang dibagikan guru via WhatsApp / QR Code.
   - **Upload Fleksibel:** Mendukung drag & drop **1 file tunggal**, **banyak file**, maupun **1 folder proyek utuh** (`webkitdirectory`).
   - **Penyimpanan Identitas Siswa:** Nama dan kelas tersimpan secara otomatis di `localStorage` browser siswa, memunculkan modal 1-klik untuk pengumpulan tugas berikutnya.
   - **Kartu Tanda Terima Digital:** Menghasilkan kode resi unik (contoh: `KMP-20261126-XXXX`), rincian file, status ketepatan waktu, dan tombol cetak bukti pengumpulan.

2. **Panel Admin Guru (`/admin` - Filament v3):**
   - **Multi-Akun Google Drive & Auto-Failover:** Menghubungkan beberapa akun Google Drive langsung dari dashboard via tombol OAuth, monitor kuota live, dan peralihan otomatis ke akun cadangan jika kuota Google Drive utama penuh.
   - **Master Data:** Manajemen Kelas (`X DKV 1`, `XI DKV 1`, dll.) dan Mata Pelajaran (`Perangkat Lunak Desain`, `Nirmana 2D`, dll.).
   - **Manajemen Tugas:** Slug otomatis, whitelist ekstensi file, batas ukuran MB, deadline, tombol Salin Link & Modal QR Code.
   - **Rekapitulasi Pengumpulan:** Filter status ketepatan waktu, tautan langsung "Buka di Drive", dan **Export Excel/CSV**.

3. **Arsitektur Penyimpanan & Pembersihan:**
   - **Hierarki Otomatis di Google Drive:** `[Root] / [Kelas] / [Mata Pelajaran] / [Judul Tugas]`.
   - **Resumable / Chunked Upload:** 5 MB/chunk untuk menangani file desain atau video ukuran besar.
   - **Auto-Cleanup Lokal:** File sementara di `storage/app/temp_uploads` selalu dihapus secara otomatis segera setelah diunggah ke Google Drive (`try...finally`).

---

## 🛠️ Tech Stack & Persyaratan Sistem

- **PHP:** 8.2 atau 8.3+ (Ekstensi: `pdo_mysql`, `curl`, `fileinfo`, `iconv`, `intl`, `mbstring`, `zip`, `openssl`)
- **Backend:** Laravel 11
- **Admin Panel:** Filament v3
- **Database:** MySQL 8.x / MariaDB
- **Frontend:** Blade, Tailwind CSS, Alpine.js
- **Cloud Storage:** Google Drive API v3 (`google/apiclient`)
- **QR Code:** `bacon/bacon-qr-code` (SVG murni, tanpa perlu `ext-gd`)

---

## ⚙️ Rekomendasi Konfigurasi Server

### 1. PHP (`php.ini`)
Untuk mendukung pengunggahan file tugas multimedia/desain besar (50 MB – 500 MB):
```ini
upload_max_filesize = 512M
post_max_size = 520M
max_execution_time = 600
max_input_time = 600
memory_limit = 512M
```

### 2. Nginx
```nginx
client_max_body_size 512M;
```

---

## 📦 Panduan Instalasi & Menjalankan Proyek

### 1. Clone & Install Dependencies
```bash
composer install
```

### 2. Konfigurasi Environment (`.env`)
Salin `.env.example` ke `.env` dan sesuaikan koneksi database MySQL:
```env
APP_NAME="Kumpulne"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kumpulne
DB_USERNAME=root
DB_PASSWORD=

# Kredensial Google OAuth (Opsional - Bisa juga diisi dari Panel Admin)
GOOGLE_DRIVE_CLIENT_ID=
GOOGLE_DRIVE_CLIENT_SECRET=
GOOGLE_DRIVE_REDIRECT_URI="${APP_URL}/admin/google-drive/callback"
GOOGLE_DRIVE_CHUNK_SIZE_MB=5
```

### 3. Migrasi & Data Awal (Seeding)
```bash
php artisan migrate --seed
```

Data default setelah seeding:
- **Akun Guru/Admin:** `admin@smkn1labuhanmaringgai.sch.id`
- **Password:** `admin123`
- **Contoh Kelas:** `X DKV 1`, `X DKV 2`, `XI DKV 1`, `XI DKV 2`, `XII DKV`
- **Contoh Mapel:** `Perangkat Lunak Desain`, `Fotografi & Videografi`, `Desain Publikasi`, `Nirmana 2D & 3D`, dll.

### 4. Menjalankan Server Lokal
```bash
php artisan serve
```
- Akses Portal Siswa: [http://localhost:8000](http://localhost:8000)
- Akses Admin Panel Filament: [http://localhost:8000/admin](http://localhost:8000/admin)

---

## 🧪 Menjalankan Pengujian Otomatis (Test Suite)
Untuk memverifikasi seluruh fungsionalitas (portal siswa, upload file, multi-file/folder, deteksi ekstensi terlarang, pembersihan file lokal, dan panel admin):
```bash
php artisan test
```
Semua 11 pengujian otomatis (32 assertions) telah diverifikasi berjalan sukses (`PASS`).
