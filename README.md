# FinTrack (Gengs FinTrack)

Aplikasi pencatatan keuangan pribadi modern, responsif, aman, dan berstandar internasional yang terintegrasi secara otomatis dengan **Google Sheets API**. Dibangun menggunakan **Laravel 12**, **Vue 3 Composition API**, **Inertia.js v2**, **Tailwind CSS**, dan basis data **PostgreSQL**.

---

## 🌟 Fitur Utama

### 1. Dashboard Finansial Terpadu
- **Ringkasan Saldo**: Total saldo seluruh rekening aktif, pemasukan bulan berjalan, pengeluaran bulan berjalan, dan arus kas bersih (*net cashflow*).
- **Grafik Tren Bulanan**: Visualisasi bar chart interaktif perbandingan arus kas bulanan menggunakan **Chart.js**.
- **Komposisi Pengeluaran**: Visualisasi doughnut chart persentase pengeluaran per kategori.
- **Daftar Transaksi Terbaru**: Menampilkan mutasi rekening teranyar dengan badge status warna.
- **Ringkasan Realisasi Anggaran**: Memantau kategori yang mendekati atau melebihi pagu anggaran secara cepat.

### 2. Multi-Rekening (Accounts Management)
- Mendukung berbagai jenis rekening: **Tunai (Cash)**, **Bank**, **E-Wallet**, **Investasi**, dan **Kartu Kredit**.
- Saldo awal dan perhitungan saldo dinamis berbasis mutasi riil.
- Proteksi penghapusan aman: rekening yang memiliki transaksi dicegah untuk dihapus sembarangan.
- Fitur penonaktifan rekening (*is_active*).

### 3. Manajemen Kategori Berjenjang (Categories Management)
- Klasifikasi tipe kategori: **Pemasukan (Income)** dan **Pengeluaran (Expense)**.
- Dilengkapi kustomisasi warna HEX dan pemilihan ikon dinamis (Lucide Icons).
- Seeder bawaan 15+ kategori populer Indonesia (Gaji, Makanan, Transportasi, Tagihan, Hiburan, dll).

### 4. Transaksi & Kalkulasi Saldo Idempoten
- CRUD transaksi komprehensif dengan identitas **UUID**.
- Fitur pencarian teks, filter rentang tanggal, filter rekening, kategori, dan jenis transaksi.
- Pagination dan penanganan format desimal Rupiah secara presisi.
- Perhitungan saldo konsisten di dalam database transaction.

### 5. Anggaran Bulanan (Monthly Budgets)
- Penetapan plafon anggaran per kategori per bulan (format `YYYY-MM`).
- Kalkulasi realisasi pengeluaran, sisa anggaran, dan persentase penggunaan secara otomatis.
- Indikator visual progres: bar hijau (aman), kuning (mendekati batas 80%), dan merah (over-budget).

### 6. Laporan Finansial & Ekspor CSV
- Laporan arus kas harian, bulanan, tahunan, atau rentang tanggal kustom.
- Ringkasan akumulasi per kategori dan per rekening.
- **Ekspor CSV**: Pengunduhan data transaksi terfilter dengan encoding **UTF-8 BOM** agar terbaca rapi di Microsoft Excel.

### 7. Integrasi Google Sheets Server-Side (Web UI Configured)
- **Non-Hardcoded**: Unggah file Service Account JSON atau tempel teks kredensial langsung melalui antarmuka web (`/sheets`).
- **Auto-Extract URL**: Menempelkan link lengkap Google Spreadsheet akan diekstrak ID-nya secara otomatis.
- **Enkripsi Kredensial Database**: Kunci privat Google disimpan aman di PostgreSQL dengan enkripsi AES bawaan Laravel (`'service_account_credentials' => 'encrypted:array'`).
- **Sinkronisasi Otomatis 3 Worksheet**:
  1. `Transactions`: Baris riwayat transaksi lengkap dengan identitas unik idempoten.
  2. `Monthly Summary`: Rekapitulasi pemasukan, pengeluaran, dan selisih per bulan.
  3. `Category Summary`: Akumulasi pengeluaran per kategori.
- **Antrean Latar Belakang (Database Queue)**: Menggunakan queue worker dengan percobaan ulang (*retry* 3x) dan penanganan backoff sehingga transaksi web tidak terhambat.
- **Fitur Bantuan Pengguna**: Tombol uji koneksi instan, tombol 1-klik salin email Service Account, dan tombol sinkronisasi massal (*Sync All*).

### 8. Keamanan & Pengaturan Akun
- Autentikasi sesi Laravel dengan proteksi CSRF, password hashing, dan rate-limiting.
- Isolasi data pengguna secara ketat: seluruh query dibatasi berdasarkan pengguna yang sedang login.
- Sanitasi pesan error: token akses dan private key disamarkan dari log database (`[REDACTED_KEY]`).
- Halaman pengaturan profil, ganti kata sandi, dan preferensi lokalisasi Indonesia.

---

## 🛠️ Tech Stack

- **Backend**: Laravel 12 (PHP 8.3+)
- **Frontend**: Vue 3 (Composition API, `<script setup>`)
- **Adapter SPA**: Inertia.js v2
- **Styling**: Tailwind CSS
- **Database**: PostgreSQL 15/16 (UUID Primary Keys)
- **Queue Worker**: Laravel Database Queue
- **Spreadsheet API**: Google Client Services API (`google/apiclient`)
- **Grafik & Ikon**: Chart.js & Lucide Vue Icons
- **Pengujian**: Pest PHP (86 Automated Tests)
- **Formatting**: Laravel Pint

---

## 🚀 Panduan Instalasi Lokal (Local Development)

### Prasyarat
- PHP >= 8.3 dengan ekstensi `pdo_pgsql`, `bcmath`, `curl`, `mbstring`, `zip`, `pcntl`
- PostgreSQL Server
- Composer 2+
- Node.js >= 20 & npm

### Langkah Instalasi

1. **Clone repository**:
   ```bash
   git clone https://github.com/agengwahid/gengs-fintrack.git
   cd gengs-fintrack
   ```

2. **Install dependensi**:
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi Database di `.env`**:
   ```ini
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=gengs_fintrack
   DB_USERNAME=postgres
   DB_PASSWORD=rahasia
   QUEUE_CONNECTION=database
   ```

5. **Jalankan Migrasi & Seeder**:
   ```bash
   php artisan migrate
   php artisan db:seed --class=CategorySeeder
   ```

6. **Jalankan Pengujian (Testing)**:
   ```bash
   ./vendor/bin/pest
   ```

7. **Jalankan Server Lokal**:
   - Terminal 1 (Laravel Server):
     ```bash
     php artisan serve
     ```
   - Terminal 2 (Vite HMR):
     ```bash
     npm run dev
     ```
   - Terminal 3 (Queue Worker untuk Google Sheets):
     ```bash
     php artisan queue:work
     ```

Buka browser Anda di `http://localhost:8000`.

---

## 🚢 Panduan Deployment Server Produksi

Untuk panduan lengkap setup di VPS Ubuntu (Nginx, Supervisor, SSL Let's Encrypt, dan Cron Scheduler), silakan baca file:
👉 **[DEPLOYMENT.md](DEPLOYMENT.md)**

---

## 📄 Lisensi

Aplikasi ini dilisensikan di bawah [MIT License](LICENSE).
