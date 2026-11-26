# Product Requirements Document (PRD): **Kumpulne**
**Sistem Portal Pengumpulan Tugas Siswa Tanpa Login (Folder Dropzone & Direct-to-Google Drive)**

---

## 1. Ringkasan Proyek
**Kumpulne** adalah aplikasi web berbasis **Laravel 11**, **Filament v3**, dan **Tailwind CSS** yang dirancang untuk memudahkan siswa mengumpulkan tugas sekolah maupun file praktik tanpa harus login ke akun Google maupun membuat akun aplikasi.

Siswa dapat mengunggah tugas melalui **Grid Kartu Folder** di halaman beranda maupun langsung melalui **Tautan Khusus Tugas (`/kumpul/{slug}`)** yang dibagikan guru lewat grup WhatsApp atau QR Code. **Kumpulne** mendukung *drag & drop* satu file, banyak file sekaligus, maupun satu folder proyek utuh, menyimpan identitas siswa di `localStorage` untuk pengiriman berikutnya, serta menyalurkan file langsung ke Google Drive milik guru/sekolah menggunakan **Google Drive API v3** dengan fitur **Multi-Akun & Auto-Failover** yang dikelola sepenuhnya dari panel admin tanpa perlu mengubah kode program.

---

## 2. Tech Stack & Lingkungan Sistem
* **Backend Framework:** Laravel 11 (PHP 8.2+)
* **Admin Panel:** Filament v3
* **Frontend & Interaksi:** Blade Templates, Tailwind CSS, Alpine.js (penanganan drag & drop, pembacaan struktur folder, `localStorage`, dan *real-time upload progress*)
* **Database:** MySQL (kompatibel dengan Laragon di Windows maupun Ubuntu Server/Docker)
* **Cloud Storage:** Google Drive API v3 (`google/apiclient`) dengan autentikasi OAuth 2.0 Refresh Token dinamis dari database
* **QR Code Generator:** `simplesoftwareio/simple-qrcode` (atau generator SVG/Canvas ringan di sisi klien)
* **Web Server Target:** Nginx / Apache (mendukung *reverse proxy* Cloudflare Tunnel)

---

## 3. Arsitektur & Pengalaman Pengguna (UI/UX)

### A. Portal Siswa (Publik — Tanpa Login)
1. **Halaman Beranda (`/`) — Grid Kartu Folder Tugas:**
   * Menampilkan branding **Kumpulne** beserta identitas sekolah (**SMKN 1 Labuhan Maringgai**) dan indikator identitas siswa di pojok kanan atas berdasarkan `localStorage` (contoh: *"Halo, Ahmad Fauzi — XI DKV 1 [Ganti]"*).
   * Menyediakan filter cepat berdasarkan **Kelas**, **Mata Pelajaran**, dan kotak pencarian judul tugas.
   * Menampilkan daftar tugas aktif dalam bentuk **Grid Kartu Folder (Card Dropzone)** berisi ikon folder, Judul Tugas, Mata Pelajaran, Target Kelas, informasi ekstensi file yang diizinkan, dan badge **Deadline**.
2. **Halaman Fokus Upload Langsung (`/kumpul/{slug}`):**
   * Rute publik khusus untuk tautan langsung yang dibagikan guru ke grup kelas.
   * Menampilkan **1 area Dropzone besar** yang berfokus hanya pada tugas tersebut, lengkap dengan deskripsi/instruksi pengerjaan, batas ukuran file, dan hitung mundur *deadline*.
   * Jika status tugas nonaktif (`is_active = false`), halaman menampilkan informasi bahwa pengumpulan untuk tugas tersebut telah ditutup.
3. **Interaksi Drag & Drop (File & Folder Utuh) + `localStorage`:**
   * Siswa dapat menyeret (*drag & drop*) **1 file**, **banyak file**, atau **1 folder proyek utuh** (mendukung `webkitdirectory` dan `webkitGetAsEntry()`), maupun mengetuk kotak Dropzone untuk memilih file dari perangkat HP/Laptop.
   * **Pop-up Kilat Identitas Siswa:**
     * Jika data siswa belum tersimpan di `localStorage`, muncul modal ringkas yang hanya meminta **Nama Lengkap** dan **Kelas** (otomatis terkunci jika tugas dikhususkan untuk 1 kelas tertentu).
     * Jika data sudah tersimpan di `localStorage`, muncul konfirmasi 1-klik (*"Kirim sebagai Ahmad Fauzi (XI DKV 1)?"*) sehingga siswa tidak perlu mengetik ulang pada pengiriman berikutnya.
   * **Progres & Kartu Tanda Terima Digital:** Menampilkan *progress bar* persentase unggahan secara *real-time*, diikuti munculnya **Kartu Bukti Pengumpulan** berisi kode resi unik (contoh: `KMP-20261126-8492`), rincian file yang terkirim, waktu kirim, dan tombol salin/unduh bukti.

### B. Dashboard Admin Guru (`/admin` — Filament v3)
1. **Manajemen Akun Google Drive (`DriveAccountResource`):**
   * Admin dapat menambahkan beberapa akun Google Drive (misal: *Drive Utama*, *Drive Cadangan 1*, *Drive Cadangan 2*) tanpa mengubah kode atau file `.env`.
   * **Tombol "Hubungkan Akun Google (OAuth)":** Mengarahkan admin ke layar persetujuan Google OAuth (`access_type=offline`, `prompt=consent`) untuk otomatis mengambil dan menyimpan `refresh_token` serta `google_email` ke database.
   * **Sakelar Akun Aktif & Cek Kuota:** Admin dapat memindahkan status aktif ke akun lain kapan saja dengan satu klik, serta menekan tombol **"Cek Kuota"** untuk memperbarui informasi kapasitas terpakai vs batas maksimal dari Google Drive API.
2. **Manajemen Kelas & Mata Pelajaran:**
   * CRUD master data **Kelas** (`X DKV 1`, `XI DKV 2`, dll.) dan **Mata Pelajaran** (`Dasar-Dasar DKV`, `Nirmana 2D`, `Pengolahan Gambar Vektor`, dll.).
3. **Manajemen Folder Tugas (`AssignmentResource`):**
   * Form pembuatan tugas: Judul Tugas, otomatis menghasilkan `slug` unik, Mata Pelajaran, Target Kelas (spesifik satu kelas atau semua kelas), Instruksi Tugas, Ekstensi file yang diizinkan, Maksimal ukuran file (MB), Deadline, dan sakelar **Aktif/Nonaktif**.
   * **Fitur Berbagi Cepat:** Setiap baris tugas memiliki tombol **"Salin Link Siswa"** (menyalin URL `/kumpul/{slug}`) dan tombol **"QR Code"** (menampilkan modal QR Code untuk dipindai siswa di kelas).
4. **Rekapitulasi & Monitoring (`SubmissionResource`):**
   * Tabel riwayat pengumpulan dengan filter Kelas, Mata Pelajaran, Judul Tugas, dan status ketepatan waktu (*Tepat Waktu* vs *Terlambat*).
   * Tombol **"Buka di Drive"** untuk melihat file/folder siswa langsung di Google Drive (tetap mengarah ke akun Drive tempat file tersebut disimpan).
   * Fitur **Export Excel (`.xlsx`) / CSV** untuk rekapitulasi nilai.

---

## 4. Arsitektur Backend & Integrasi Google Drive API v3

### A. Manajemen Multi-Akun & Auto-Failover (`GoogleDriveService`)
1. **Pengambilan Kredensial Dinamis:** Layanan membaca kredensial (`client_id`, `client_secret`, `refresh_token`, `root_folder_id`) dari tabel `drive_accounts` yang memiliki `is_active = true` (dengan fallback ke `.env` untuk `client_id` dan `client_secret` default).
2. **Auto-Failover Saat Kuota Penuh:** Apabila proses unggah gagal karena error kuota Google Drive penuh (`storageQuotaExceeded`), sistem otomatis:
   * Menandai akun aktif saat ini sebagai `is_full = true` dan `is_active = false`.
   * Mengaktifkan akun cadangan berikutnya berdasarkan urutan kolom `priority` yang memiliki `is_full = false`.
   * Mengulang proses unggah file siswa ke akun baru secara transparan tanpa memunculkan error kepada siswa.

### B. Hierarki Folder & Aturan Penamaan di Google Drive
Di dalam `root_folder_id` pada akun Drive yang aktif, **Kumpulne** otomatis menyusun struktur folder berikut:
```text
📁 [Root Folder Kumpulne]
 └── 📁 [Nama Kelas] (contoh: XI DKV 1)
      └── 📁 [Mata Pelajaran] (contoh: Perangkat Lunak Desain)
           └── 📁 [Judul Tugas] (contoh: Tugas 01 - Desain Kemasan)
                ├── 📄 XI-DKV-1_Budi-Santoso_Kemasan-Kopi.pdf       <-- (Upload 1 file)
                └── 📁 XI-DKV-1_Siti-Aminah/                        <-- (Upload banyak file / folder)
                     ├── 📄 desain-utama.cdr
                     └── 📄 mockup-3d.png
```
* **Upload 1 File Tunggal:** File disimpan langsung di dalam folder `[Judul Tugas]` dan di-rename otomatis menjadi `[KELAS]_[NAMA-SISWA]_[NAMA-FILE-ASLI].[EXT]`.
* **Upload Multi-File atau Satu Folder Utuh:** Sistem membuatkan subfolder `[KELAS]_[NAMA-SISWA]` di dalam `[Judul Tugas]` dan mengunggah seluruh file ke dalamnya dengan mempertahankan struktur subfolder asli.

### C. Ketahanan Upload File Besar & Pembersihan Lokal
* **Chunked / Resumable Upload:** Menggunakan `Google\Http\MediaFileUpload` (`setDefer(true)`) dengan potongan **5 MB** per siklus agar file desain atau video berukuran besar (50 MB – 500 MB) tetap stabil saat diunggah.
* **Auto-Cleanup Penyimpanan Server:** File sementara yang ditampung di `storage/app/temp_uploads/{batch_id}` **wajib dihapus otomatis** di dalam blok `try...finally` segera setelah proses unggah ke Google Drive selesai (baik berhasil maupun gagal) agar disk server tidak penuh.

---

## 5. Desain Skema Database (MySQL)

| Nama Tabel | Kolom Utama | Tipe Data & Keterangan |
| :--- | :--- | :--- |
| **`users`** | `id`, `name`, `email`, `password` | Akun admin/guru pengelola sistem. |
| **`drive_accounts`** | `id`, `account_name`, `google_email`, `client_id`, `client_secret`, `refresh_token`, `root_folder_id`, `storage_used_bytes`, `storage_limit_bytes`, `priority`, `is_active`, `is_full` | Daftar akun Google Drive terhubung untuk rotasi penyimpanan dinamis. |
| **`classrooms`** | `id`, `name`, `level`, `is_active` | Daftar kelas aktif (`X DKV 1`, `XI DKV 1`, dll.). |
| **`subjects`** | `id`, `code`, `name`, `is_active` | Daftar mata pelajaran aktif. |
| **`assignments`** | `id`, `subject_id`, `classroom_id` (nullable), `title`, `slug` (unique), `description`, `allowed_extensions`, `max_file_size_mb`, `deadline`, `is_active` | Master kartu folder tugas dan konfigurasi URL `/kumpul/{slug}`. |
| **`submissions`** | `id`, `receipt_code`, `drive_account_id`, `assignment_id`, `classroom_id`, `student_name`, `upload_type` (`single_file`/`batch_folder`), `file_count`, `total_size_bytes`, `gdrive_target_id`, `gdrive_web_view_link`, `is_late`, `ip_address`, `submitted_at` | Log pengumpulan tugas beserta referensi akun Drive penyimpanan file. |
| **`submission_items`** | `id`, `submission_id`, `original_filename`, `renamed_filename`, `relative_path`, `file_size_bytes`, `mime_type`, `gdrive_file_id`, `gdrive_web_view_link` | Rincian tiap file apabila siswa mengunggah banyak file atau folder. |

---

## 6. Konfigurasi Environment, Keamanan & Deployment

### Variabel `.env` (Konfigurasi Default OAuth)
```env
APP_NAME="Kumpulne"
GOOGLE_DRIVE_CLIENT_ID=
GOOGLE_DRIVE_CLIENT_SECRET=
GOOGLE_DRIVE_REDIRECT_URI="${APP_URL}/admin/google-drive/callback"
GOOGLE_DRIVE_CHUNK_SIZE_MB=5
```

### Keamanan & Validasi
1. **Filter Ekstensi & MIME Type:** Validasi ganda (frontend & backend) sesuai daftar ekstensi yang diizinkan pada tugas, serta pemblokiran mutlak file berbahaya (`.php`, `.exe`, `.sh`, `.bat`, `.cmd`, `.js`, `.html`).
2. **Sanitasi Nama File & Folder:** Menghapus karakter ilegal (`/`, `\`, `:`, `*`, `?`, `"`, `<`, `>`, `|`) dari input nama siswa sebelum membentuk nama file/folder di Google Drive.
3. **Rate Limiting:** Terapkan pembatasan request (`throttle:20,1`) pada endpoint unggah publik untuk mencegah penyalahgunaan.
4. **Konfigurasi Server (Sertakan di `README.md`):**
   * PHP (`php.ini`): `upload_max_filesize = 512M`, `post_max_size = 520M`, `max_execution_time = 600`, `memory_limit = 512M`.
   * Nginx: `client_max_body_size 512M;`.

---

## 7. Kriteria Keberhasilan (Acceptance Criteria)
- [ ] Siswa dapat membuka beranda (`/`) maupun tautan langsung tugas (`/kumpul/{slug}`) tanpa login, lalu mengunggah satu file, banyak file, atau satu folder utuh via *drag & drop*.
- [ ] Identitas siswa (Nama & Kelas) otomatis tersimpan dan terisi kembali melalui `localStorage` pada pengiriman berikutnya.
- [ ] File tersimpan otomatis di Google Drive sesuai struktur `[Root Folder] / [Kelas] / [Mata Pelajaran] / [Judul Tugas]` dengan penamaan otomatis yang rapi.
- [ ] Admin dapat menambah akun Google Drive baru via tombol OAuth di panel `/admin` dan memindahkan akun aktif kapan saja tanpa mengubah kode program, serta sistem otomatis berpindah akun jika kuota penuh.
- [ ] Admin dapat menyalin tautan langsung `/kumpul/{slug}`, menampilkan QR Code tugas, membuka hasil karya siswa di Google Drive, dan mengekspor rekap ke Excel/CSV.
- [ ] Tidak ada file sisa yang menumpuk di folder lokal `storage/app/temp_uploads` setelah proses upload selesai.