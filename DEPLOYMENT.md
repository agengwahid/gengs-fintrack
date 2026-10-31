# Panduan Deployment Server Produksi - FinTrack (Gengs FinTrack)

Panduan ini mendokumentasikan langkah demi langkah untuk melakukan deploy aplikasi **FinTrack** ke server produksi (VPS seperti DigitalOcean, Linode, AWS EC2, IDCloudHost, atau server Ubuntu 22.04 / 24.04 LTS).

---

## 1. Spesifikasi Minimum Server

- **OS**: Ubuntu 22.04 LTS atau 24.04 LTS (64-bit)
- **RAM**: Minimal 1 GB (disarankan 2 GB + 1 GB Swap)
- **CPU**: 1 vCPU atau lebih
- **Disk**: Minimal 15 GB SSD
- **Stack**:
  - PHP 8.3+ (CLI & FPM)
  - PostgreSQL 15+ atau 16+
  - Nginx Web Server
  - Node.js 20 LTS & npm
  - Composer 2+
  - Supervisor (untuk background worker Google Sheets)
  - Certbot (untuk SSL HTTPS gratis dari Let's Encrypt)

---

## 2. Persiapan Server & Instalasi Dependensi

Jalankan perintah berikut di terminal server (sebagai user `root` atau `sudo`):

```bash
# Update sistem
sudo apt update && sudo apt upgrade -y

# Install dependensi dasar
sudo apt install -y software-properties-common curl git unzip ufw supervisor

# Tambahkan repositori PHP Ondrej
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.3 dan ekstensi yang dibutuhkan
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common \
    php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
    php8.3-bcmath php8.3-intl php8.3-readline php8.3-pcntl

# Install Nginx & PostgreSQL
sudo apt install -y nginx postgresql postgresql-contrib

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 3. Konfigurasi Basis Data PostgreSQL

Buat user dan database khusus untuk FinTrack:

```bash
# Masuk ke PostgreSQL prompt
sudo -u postgres psql
```

Jalankan query SQL berikut (ganti `password_aman_anda` dengan password yang kuat):

```sql
CREATE DATABASE fintrack_production;
CREATE USER fintrack_user WITH ENCRYPTED PASSWORD 'password_aman_anda';
GRANT ALL PRIVILEGES ON DATABASE fintrack_production TO fintrack_user;
ALTER DATABASE fintrack_production OWNER TO fintrack_user;
\q
```

---

## 4. Unduh Proyek & Setup Direktori

```bash
# Buat direktori web
sudo mkdir -p /var/www/fintrack
sudo chown -R $USER:$USER /var/www/fintrack

# Clone repository
git clone https://github.com/agengwahid/gengs-fintrack.git /var/www/fintrack
cd /var/www/fintrack

# Salin file konfigurasi lingkungan (.env)
cp .env.example .env
```

Sesuaikan file `.env` untuk server produksi:

```bash
nano .env
```

Pastikan variabel-variabel penting di bawah ini telah disesuaikan:

```ini
APP_NAME=FinTrack
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://fintrack.domainanda.com
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=en

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fintrack_production
DB_USERNAME=fintrack_user
DB_PASSWORD=password_aman_anda

QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

# Google Sheets fallback (opsional, karena konfigurasi bisa via Web UI)
GOOGLE_SHEETS_ENABLED=true
GOOGLE_SHEETS_SPREADSHEET_ID=
GOOGLE_SHEETS_CREDENTIALS_PATH=storage/app/google/credentials.json
```

---

## 5. Build Aset & Inisialisasi Aplikasi

```bash
cd /var/www/fintrack

# Install dependensi PHP (mode produksi)
composer install --no-dev --optimize-autoloader

# Generate APP_KEY
php artisan key:generate --force

# Install dependensi Node.js & build frontend (Vite)
npm ci
npm run build

# Jalankan migrasi database
php artisan migrate --force

# Jalankan seeder kategori awal (jika diperlukan)
php artisan db:seed --class=CategorySeeder --force

# Optimalkan cache Laravel untuk performa produksi maksimal
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 6. Atur Hak Akses File (Permissions)

```bash
# Berikan kepemilikan direktori ke www-data (user Nginx)
sudo chown -R www-data:www-data /var/www/fintrack
sudo find /var/www/fintrack -type f -exec chmod 644 {} \;
sudo find /var/www/fintrack -type d -exec chmod 755 {} \;

# Berikan hak tulis ke storage dan bootstrap/cache
sudo chmod -R 775 /var/www/fintrack/storage
sudo chmod -R 775 /var/www/fintrack/bootstrap/cache
```

---

## 7. Konfigurasi Supervisor (Worker Google Sheets Otomatis)

Supervisor menjamin antrean pengiriman Google Sheets berjalan 24 jam nonstop dan otomatis *restart* jika terjadi *crash* atau server *reboot*.

Buat file konfigurasi Supervisor:

```bash
sudo nano /etc/supervisor/conf.d/fintrack-worker.conf
```

Isi dengan konfigurasi berikut:

```ini
[program:fintrack-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/fintrack/artisan queue:work database --queue=default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/fintrack/storage/logs/worker.log
stopwaitsecs=3600
```

Aktifkan konfigurasi Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start fintrack-worker:*

# Periksa status worker (pastikan RUNNING)
sudo supervisorctl status
```

---

## 8. Konfigurasi Task Scheduler (Cron Job)

Laravel Scheduler bertugas menjalankan sinkronisasi massal harian (`fintrack:sync-sheets`) otomatis setiap pukul 23:59 WIB.

Buka crontab user `www-data`:

```bash
sudo crontab -u www-data -e
```

Tambahkan baris berikut di bagian paling bawah:

```cron
* * * * * cd /var/www/fintrack && php artisan schedule:run >> /dev/null 2>&1
```

---

## 9. Konfigurasi Nginx Web Server

Buat virtual host Nginx untuk FinTrack:

```bash
sudo nano /etc/nginx/sites-available/fintrack
```

Isi konfigurasi berikut (sesuaikan `server_name` dengan domain Anda):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name fintrack.domainanda.com;
    root /var/www/fintrack/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php index.html;
    charset utf-8;

    # Gzip Compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript image/svg+xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # Cache static build assets
    location ~* \.(?:css|js|jpg|jpeg|gif|png|ico|cur|gz|svg|svgz|mp4|ogg|ogv|webm|htc|woff|woff2)$ {
        expires 1y;
        access_log off;
        add_header Cache-Control "public, max-age=31536000, immutable";
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan konfigurasi dan restart Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/fintrack /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 10. Pasang SSL HTTPS Gratis (Certbot)

Amankan koneksi web menggunakan SSL Let's Encrypt:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d fintrack.domainanda.com
```

Pilih opsi *Redirect HTTP to HTTPS*. Certbot akan otomatis memperbarui sertifikat SSL sebelum kedaluwarsa.

---

## 11. Prosedur Pembaruan Aplikasi (Deploy Update)

Jika Anda melakukan perubahan kode di Git dan ingin mengunggahnya ke server:

```bash
cd /var/www/fintrack

# Ambil perubahan terbaru
git pull origin main

# Install dependensi PHP & JS terbaru
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Migrasi database jika ada tabel baru
php artisan migrate --force

# Refresh cache Laravel
php artisan optimize

# Restart worker agar menggunakan kode terbaru
sudo supervisorctl restart fintrack-worker:*
```

---

## 12. Backup Basis Data PostgreSQL Otomatis

Untuk mencadangkan data secara berkala:

```bash
# Backup manual
pg_dump -U fintrack_user -h 127.0.0.1 fintrack_production > backup_$(date +%Y%m%d).sql

# Restore jika diperlukan
psql -U fintrack_user -h 127.0.0.1 fintrack_production < backup_20261031.sql
```
