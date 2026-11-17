# NewJeans Fan Hub

Website fan-made bertema NewJeans: beranda, diskografi, jadwal, berita, profil member, dan galeri. Aplikasi ini berupa server-rendered PHP + MySQL dengan panel admin untuk mengelola seluruh konten. Tidak ada build step frontend.

## Teknologi

- PHP >= 8.3, dengan ekstensi `pdo_mysql` dan `fileinfo`
- MySQL 8.4 atau MariaDB 11.x
- Composer 2
- CSS dan JavaScript vanilla (Locomotive Scroll, AdminLTE untuk admin)

## Struktur folder

```text
.
├── public/                     # docroot
│   ├── index.php               # front controller + definisi route
│   ├── .htaccess               # rewrite non-file request ke index.php
│   └── assets/                 # CSS, JS, gambar, audio, video, vendor
├── app/
│   ├── Core/                   # Router, Database, Auth, Csrf, Upload, dll.
│   ├── Controllers/            # controller Public dan Admin
│   ├── Models/                 # repository PDO per tabel
│   ├── Payments/               # PaymentGateway interface + MockPaymentGateway
│   ├── Services/               # CheckoutService (order & payment)
│   ├── Shipping/               # DestinationCatalog & ShippingCalculator
│   └── Support/                # helper, Cart, dan utilitas slug
├── config/                     # app.php, database.php, shipping.php
├── database/
│   ├── schema.sql              # skema 11 tabel (9 konten + orders, order_items)
│   ├── seed.php                # seeder data awal
│   ├── build-shipping-data.php # generator dataset kota GeoNames
│   ├── data/shipping/          # countries.json, cities/{ISO2}.json, LICENSE, README.md
│   └── seed-data/news.json     # sumber seed berita
├── templates/
│   ├── layouts/                # layout public dan admin
│   ├── partials/               # header, footer, kartu berita/event
│   ├── pages/                  # halaman publik
│   └── admin/                  # halaman panel admin
├── storage/                    # upload/log non-publik
├── archive/                    # aset HTML/JS legacy (di luar docroot)
├── docs/                       # panduan desain dan catatan perencanaan
├── .env.example
└── composer.json
```

## Prasyarat dan setup

1. Install dependency:

   ```powershell
   composer install
   ```

2. Salin konfigurasi environment lalu sesuaikan kredensial database:

   ```powershell
   Copy-Item .env.example .env
   ```

   ```dotenv
   APP_ENV=local
   APP_URL=http://localhost:8000
   APP_DEBUG=true

   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=newjeans
   DB_USER=root
   DB_PASS=

   # Album purchase shipping simulation (kosongkan salah satu untuk menonaktifkan checkout)
   SHIPPING_BASE_FEE_USD=3.00
   SHIPPING_FEE_PER_KM_USD=0.0015
   ```

3. Buat database:

   ```sql
   CREATE DATABASE newjeans CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. Import skema:

   ```powershell
   mysql -h 127.0.0.1 -P 3306 -u root newjeans < database/schema.sql
   ```

5. Isi data awal:

   ```powershell
   php database/seed.php --fresh
   ```

   Gunakan `--fresh` untuk mengosongkan tabel konten lalu mengisi ulang. Tanpa flag tersebut, seeder menolak berjalan jika data sudah ada.

6. Jalankan server dari root proyek (docroot harus `public/`):

   ```powershell
   php -S localhost:8000 -t public
   ```

7. Buka `http://localhost:8000`.

## Akun admin

Seeder membuat satu akun admin:

| Email | Password |
|---|---|
| `admin@newjeans.local` | `admin123` |

Kredensial ini hanya untuk lingkungan lokal/demo. Ganti password sebelum dipakai di lingkungan nyata (ubah data di tabel `users` atau sesuaikan `database/seed.php`).

Panel admin tersedia di `http://localhost:8000/admin`.

## Route

### Publik

| Route | Halaman |
|---|---|
| `/` | Home: lagu unggulan, galeri, berita terbaru |
| `/discography` | Daftar album + track list |
| `/schedule` | Daftar event |
| `/news` | Daftar berita published |
| `/news/{slug}` | Detail berita |
| `/about` | Timeline sejarah + profil member |
| `/gallery` | Galeri foto |
| `/cart` | Keranjang belanja sesi (`POST /cart/items`, `/cart/items/{id}`, `/cart/items/{id}/remove`) |
| `/checkout` | Guest checkout + estimasi ongkir dari Seoul (`GET`, `POST`) |
| `/shipping/cities`, `/shipping/estimate` | Endpoint JSON kota tujuan dan estimasi ongkir |
| `/checkout/{token}/payment` | Halaman simulasi pembayaran (`POST /checkout/{token}/pay`) |
| `/orders/{token}` | Status dan ringkasan pesanan guest |

### Admin (perlu login)

| Route | Fungsi |
|---|---|
| `/login`, `POST /login`, `POST /logout` | Autentikasi sesi |
| `/admin` | Dashboard + jumlah konten & pesanan |
| `/admin/album-types` | CRUD tipe album |
| `/admin/albums` | CRUD album + upload cover |
| `/admin/albums/{albumId}/songs` | CRUD lagu per album |
| `/admin/news` | CRUD berita + upload thumbnail |
| `/admin/events` | CRUD event + upload poster |
| `/admin/members` | CRUD member + upload foto |
| `/admin/histories` | CRUD history + upload gambar |
| `/admin/gallery` | CRUD item galeri + upload gambar |
| `/admin/orders`, `/admin/orders/{id}` | Daftar & detail pesanan (read-only, 20/halaman) |

Setiap route admin memakai sesi dan token CSRF. Aksi tanpa token yang valid ditolak dengan status `419`.

### Redirect legacy

| URL lama | Tujuan |
|---|---|
| `/index.html`, `/home-legacy.html` | `/` |
| `/pages/discography.html` | `/discography` |
| `/pages/schedule.html` | `/schedule` |
| `/pages/about.html` | `/about` |
| `/pages/gallery.html` | `/gallery` |
| `/pages/news.html` | `/news` |
| `/pages/news.html?slug=...` | `/news/{slug}` |

Semua redirect memakai status `301`.

## Upload

- Disimpan di `public/assets/images/uploads/{albums,news,events,members,histories,gallery}/`.
- Format yang diterima: JPEG, PNG, WebP, GIF; maksimal 5 MB.
- Nama file diacak; nama asli dari client tidak dipakai.
- File upload dan `storage/` tidak di-commit (lihat `.gitignore`).

## Data dan soft delete

Sebelas tabel database: 9 tabel konten (`users`, `album_types`, `albums`, `songs`, `news`, `events`, `members`, `histories`, `gallery_items`) dengan soft delete (`deleted_at`; query publik selalu memfilter `deleted_at IS NULL`), serta 2 tabel pesanan (`orders`, `order_items`) tanpa soft delete untuk menyimpan snapshot pembelian album.

## Pembelian album & dataset ongkir

- **Guest checkout & mock payment**: Keranjang disimpan di PHP session (`$_SESSION['cart']`) tanpa registrasi customer. Pembayaran memakai `MockPaymentGateway` (`mock`) untuk simulasi `success` dan `failure`.
- **Tarif ongkir**: Dihitung dari Seoul (`37.5665, 126.9780`) dengan rumus `SHIPPING_BASE_FEE_USD + distance_km * SHIPPING_FEE_PER_KM_USD` (contoh demo: `3.00` + `0.0015`/km). Jika salah satu variabel kosong di `.env`, checkout dinonaktifkan.
- **Dataset GeoNames**: Data negara dan kota di `database/data/shipping/` bersumber dari GeoNames (`cities15000` + suplemen ibu kota, lisensi **CC BY 4.0** — lihat `database/data/shipping/LICENSE` dan `database/data/shipping/README.md`). Untuk membangun ulang dataset dari file mentah GeoNames:

  ```powershell
  php database/build-shipping-data.php <cities15000.txt> <countryInfo.txt> <admin1CodesASCII.txt>
  ```

## Verifikasi

```powershell
# Lint seluruh PHP
Get-ChildItem -Recurse -File -Include *.php app,config,templates,public,database,tests | ForEach-Object { php -l $_.FullName }

# Cek sintaks JavaScript
Get-ChildItem -Recurse -File public/assets/js/*.js | ForEach-Object { node --check $_.FullName }

# Jalankan test unit & service (pastikan NJ_TEST_DB mengarah ke database test disposable)
php tests/shipping_calculator_test.php
php tests/cart_test.php
php tests/checkout_service_test.php
php tests/payment_service_test.php
```

Smoke test manual: buka seluruh route publik, pastikan route tidak dikenal mengembalikan `404`, lalu uji login, CRUD tiap modul, upload, dan logout di panel admin.

## Dokumentasi

- [docs/design.md](docs/design.md): warna, tipografi, layout, dan aturan komponen.
- [docs/reports/Gallery_Web_Page_Report_Gilang_3202416074.pdf](docs/reports/Gallery_Web_Page_Report_Gilang_3202416074.pdf): laporan proyek galeri.
- [docs/superpowers/plans/2026-11-09-php-backend-content.md](docs/superpowers/plans/2026-11-09-php-backend-content.md): rencana dan status implementasi backend.
