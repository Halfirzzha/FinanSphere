# FinanSphere

FinanSphere adalah aplikasi berbasis web yang dirancang untuk membantu pengguna dalam mengelola keuangan pribadi dengan mudah, aman, dan efisien. Dibangun menggunakan framework Laravel dan Filament versi terbaru, aplikasi ini menawarkan fitur-fitur canggih untuk memantau pemasukan, pengeluaran, dan laporan keuangan secara real-time.

![FinanSphere Screenshot](public/img/FinanSphere.png)

---

## ✨ Fitur Utama

- **Manajemen Keuangan**: Catat pemasukan dan pengeluaran dengan mudah.
- **Dashboard Interaktif**: Visualisasi data keuangan secara real-time.
- **Keamanan Tinggi**: Perlindungan terhadap SQL Injection, XSS, CSRF, dan lainnya.
- **Integrasi API**: Mendukung integrasi dengan layanan pihak ketiga.
- **Optimasi Performa**: Mendukung caching, database indexing, dan async processing.
- **Laporan Keuangan**: Unduh laporan keuangan dalam berbagai format.

---

## 🚀 Teknologi yang Digunakan

- **Backend**: Laravel 12.x
- **Frontend**: Filament 3.x
- **Database**: MySQL
- **Bahasa Pemrograman**: PHP 8.2

---

## 📦 Instalasi

### Prasyarat

- PHP 8.2 atau lebih baru
- Composer
- MySQL

### Langkah Instalasi

1. Clone repository ini:

   ```bash
   git clone https://github.com/Halfirzzha/FinanSphere.git
   cd FinanSphere
   ```

2. Install dependensi menggunakan Composer:

   ```bash
   composer install
   ```

3. Salin file `.env.example` menjadi `.env` dan sesuaikan konfigurasi:

   ```bash
   cp .env.example .env
   ```

4. Generate application key:

   ```bash
   php artisan key:generate
   ```

5. Migrasi database:

   ```bash
   php artisan migrate
   ```

6. Jalankan server lokal:

   ```bash
   php artisan serve
   ```

7. Akses aplikasi melalui browser di alamat:

   ```
   http://127.0.0.1:8000/finbrain
   ```

---

## 📂 Struktur Proyek

```
finansp-pro/
├── app/                # Logika aplikasi (Controllers, Models, dll.)
├── bootstrap/          # File bootstrap aplikasi
├── config/             # Konfigurasi aplikasi
├── database/           # File migrasi dan seeder
├── public/             # File yang dapat diakses publik (CSS, JS, dll.)
├── resources/          # Views dan assets frontend
├── routes/             # Definisi rute aplikasi
├── storage/            # File cache, logs, dan lainnya
├── tests/              # Pengujian aplikasi
└── vendor/             # Dependensi Composer
```

---

## 🤝 Kontribusi
Kontribusi sangat dihargai! Silakan fork repository ini dan kirimkan pull request Anda.

## 📜 Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE).
