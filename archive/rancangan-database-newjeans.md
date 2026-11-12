# Rancangan Akhir Basis Data Newjeans Band

> Laporan PBL Kelompok 5  
> Praktikum Pemrograman Web  
> Program Studi D3 Teknik Informatika  
> Jurusan Teknik Elektro  
> Politeknik Negeri Pontianak  
> Tahun 2026

---

## Identitas Laporan

| Keterangan | Detail |
|---|---|
| Judul Laporan | Rancangan Akhir Basis Data Newjeans Band |
| Mata Kuliah | Praktikum Pemrograman Web |
| Dosen Pengampu | Ferry Faisal |
| Kelompok | Kelompok 5 |
| Program Studi | D3 Teknik Informatika |
| Jurusan | Teknik Elektro |
| Institusi | Politeknik Negeri Pontianak |
| Tahun | 2026 |

### Anggota Kelompok

| No | Nama | NIM |
|---|---|---|
| 1 | Ahmad Zaki Al Gifari | 3202416052 |
| 2 | Fathur Rizky Ramadhan | 3202416061 |
| 3 | Gilang | 3202416074 |
| 4 | Rafi Nur Fattah | 3202416058 |

---

## Kata Pengantar

Puji dan syukur kami panjatkan kepada Allah SWT atas rahmat dan karunia-Nya sehingga laporan yang berjudul **“Rancangan Akhir Basis Data Newjeans Band”** dapat diselesaikan dengan baik.

Laporan ini disusun sebagai salah satu tugas pada mata kuliah **Praktikum Pemrograman Web** di Program Studi D3 Teknik Informatika, Jurusan Teknik Elektro, Politeknik Negeri Pontianak. Laporan ini membahas rancangan akhir database yang digunakan untuk website Newjeans Band, termasuk hasil revisi dari versi database sebelumnya.

---

## Pembahasan Revisi Database

Pada versi database sebelumnya, kelompok kami telah mendapatkan revisi dari dosen pengampu, yaitu **Pak Ferry Faisal**. Beberapa revisi yang diberikan adalah sebagai berikut.

### 1. Penyederhanaan Timestamp

Revisi pertama adalah membuat penggunaan timestamp menjadi lebih sederhana. Awalnya terdapat kolom seperti `created_at` dan `modified_at`. Namun, setelah diskusi kelompok, kami sepakat untuk menggunakan kolom berikut:

- `created_at`
- `deleted_at`

Alasan penggunaan kedua kolom tersebut adalah:

| Kolom | Fungsi |
|---|---|
| `created_at` | Digunakan untuk sorting data secara descending agar data terbaru dapat ditampilkan lebih dahulu. |
| `deleted_at` | Digunakan sebagai mekanisme soft delete, sehingga data hanya tampak terhapus secara visual, tetapi sebenarnya masih tersimpan di database. |

Dengan pendekatan ini, data tidak benar-benar dihapus secara permanen dari database. Jika `deleted_at` berisi nilai timestamp, maka data dianggap sudah tidak aktif atau sudah dihapus dari tampilan aplikasi.

### 2. Penambahan Kolom `release_date` pada Tabel `albums`

Revisi berikutnya adalah menambahkan kolom `release_date` pada tabel `albums`. Kolom ini sebelumnya belum ditambahkan pada versi database lama.

Kolom `release_date` berfungsi untuk menyimpan tanggal rilis album. Data ini penting karena album musik memiliki tanggal rilis yang dapat ditampilkan kepada pengguna, terutama pada halaman discography atau detail album.

### 3. Penambahan Fitur Pembelian Album

Selain revisi struktur database utama, kelompok kami juga menambahkan satu fitur baru, yaitu **fitur pembelian album**.

Pada saat laporan ini dibuat, halaman website untuk fitur pembelian album belum tersedia, sehingga penjelasan difokuskan pada struktur tabel yang dibutuhkan untuk mendukung fitur e-commerce sederhana, seperti:

- Keranjang belanja
- Item keranjang
- Pesanan
- Detail pesanan
- Opsi pengiriman
- Bukti pembayaran
- Riwayat status pesanan

---

## Daftar Tabel dalam Database

Berikut adalah daftar tabel yang digunakan dalam rancangan database Newjeans Band.

| No | Nama Tabel | Fungsi Utama |
|---|---|---|
| 1 | `users` | Menyimpan data pengguna sistem. |
| 2 | `album_types` | Menyimpan jenis/tipe album. |
| 3 | `albums` | Menyimpan data album Newjeans. |
| 4 | `songs` | Menyimpan data lagu. |
| 5 | `news` | Menyimpan data berita. |
| 6 | `events` | Menyimpan data event atau tour. |
| 7 | `members` | Menyimpan data member Newjeans. |
| 8 | `histories` | Menyimpan data histori grup. |
| 9 | `gallery_items` | Menyimpan data galeri. |
| 10 | `shipping_options` | Menyimpan opsi pengiriman. |
| 11 | `carts` | Menyimpan keranjang pengguna. |
| 12 | `cart_items` | Menyimpan item dalam keranjang. |
| 13 | `orders` | Menyimpan data pesanan. |
| 14 | `order_items` | Menyimpan detail item dalam pesanan. |
| 15 | `payment_proofs` | Menyimpan bukti pembayaran. |
| 16 | `order_status_histories` | Menyimpan riwayat perubahan status pesanan. |

---

## Enum yang Digunakan

Enum digunakan untuk membatasi nilai pada kolom tertentu agar data lebih konsisten.

### 1. Enum `user_role`

Digunakan pada tabel `users` untuk menentukan peran pengguna.

| Nilai | Keterangan |
|---|---|
| `admin` | Pengguna dengan hak akses sebagai administrator. |
| `customer` | Pengguna sebagai pelanggan atau pembeli. |

### 2. Enum `order_status`

Digunakan pada tabel `orders` dan `order_status_histories` untuk menentukan status pesanan.

| Nilai | Keterangan |
|---|---|
| `PENDING_PAYMENT` | Pesanan dibuat tetapi belum melakukan pembayaran. |
| `WAITING_APPROVAL` | Pembayaran atau bukti pembayaran menunggu persetujuan admin. |
| `COMPLETED` | Pesanan selesai. |
| `CANCELLED` | Pesanan dibatalkan. |

### 3. Enum `proof_status`

Digunakan pada tabel `payment_proofs` untuk menentukan status bukti pembayaran.

| Nilai | Keterangan |
|---|---|
| `PENDING` | Bukti pembayaran menunggu verifikasi. |
| `APPROVED` | Bukti pembayaran disetujui. |
| `REJECTED` | Bukti pembayaran ditolak. |

---

## Penjelasan Detail Tabel

---

## 1. Tabel `users`

Tabel `users` berfungsi untuk menyimpan data pengguna sistem. Pengguna dapat berperan sebagai admin maupun customer.

Admin biasanya memiliki akses untuk mengelola konten website seperti album, lagu, berita, event, member, galeri, dan verifikasi pembayaran. Sementara itu, customer adalah pengguna yang dapat melakukan pembelian album.

### Struktur Tabel `users`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID unik pengguna. |
| `name` | varchar | Not Null | Nama pengguna. |
| `email` | varchar | Not Null, Unique | Email pengguna. |
| `password` | varchar | Not Null | Password pengguna. |
| `role` | user_role | Not Null, Default: `customer` | Peran pengguna. |
| `created_at` | timestamp | - | Waktu data dibuat. |

---

## 2. Tabel `album_types`

Tabel `album_types` berfungsi untuk menyimpan jenis atau tipe album. Data pada tabel ini nantinya akan muncul pada halaman **discography**.

Penggunaan data `album_types` pada halaman discography adalah sebagai:

- Chip button filter
- Badge pada album card

Contohnya, tipe album dapat berupa single album, EP, studio album, dan lain-lain sesuai kebutuhan website.

### Struktur Tabel `album_types`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID tipe album. |
| `name` | varchar | Not Null, Unique | Nama tipe album. |
| `created_at` | timestamp | - | Waktu data dibuat. |
| `deleted_at` | timestamp | - | Waktu data dihapus secara soft delete. |

---

## 3. Tabel `albums`

Tabel `albums` berfungsi untuk menyimpan data album dari grup band Newjeans. Data album ini nantinya akan muncul pada halaman **discography** sebagai card album.

Setiap album memiliki tipe album, nama album, cover album, tanggal rilis, link Spotify, harga, stok, serta status apakah album tersebut dijual atau tidak.

### Struktur Tabel `albums`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID album. |
| `album_type_id` | integer | Foreign Key ke `album_types.id`, Not Null | Relasi ke tabel tipe album. |
| `name` | varchar | Not Null | Nama album. |
| `cover_album` | varchar | Not Null | Path atau URL cover album. |
| `release_date` | datetime | - | Tanggal rilis album. |
| `spotify_url` | varchar | - | Link Spotify album. |
| `price` | decimal(10,2) | Not Null, Default: 0 | Harga album dalam USD. |
| `stock` | integer | Not Null, Default: 0 | Stok album. |
| `is_for_sale` | boolean | Not Null, Default: false | Menentukan apakah album dijual atau tidak. |
| `created_at` | timestamp | - | Waktu data dibuat. |
| `deleted_at` | timestamp | - | Waktu data dihapus secara soft delete. |

### Penjelasan Kolom Penting

- `album_type_id`: Menghubungkan album dengan tabel `album_types`.
- `release_date`: Kolom hasil revisi untuk menyimpan tanggal rilis album.
- `price`: Digunakan untuk fitur pembelian album.
- `stock`: Digunakan untuk mengontrol ketersediaan album.
- `is_for_sale`: Menentukan apakah album dapat dibeli oleh customer atau tidak.

---

## 4. Tabel `songs`

Tabel `songs` berfungsi untuk menyimpan data lagu dari grup band Newjeans.

Penggunaan data lagu pada website adalah:

- Pada halaman **home**, khususnya section **top songs**.
- Pada halaman **discography**, terutama di dalam album card atau detail album.

Setiap lagu memiliki relasi ke album, sehingga satu album dapat memiliki banyak lagu.

### Struktur Tabel `songs`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID lagu. |
| `album_id` | integer | Foreign Key ke `albums.id`, Not Null | Relasi ke tabel album. |
| `title` | varchar | Not Null | Judul lagu. |
| `is_featured` | tinyint | Default: 0 | Penanda apakah lagu merupakan featured song. |
| `audio_url` | varchar | - | URL audio lagu. |
| `created_at` | timestamp | - | Waktu data dibuat. |
| `deleted_at` | timestamp | - | Waktu data dihapus secara soft delete. |

---

## 5. Tabel `news`

Tabel `news` berfungsi untuk menyimpan data berita atau konten informasi terkait Newjeans. Konten berita dapat berupa pengumuman, update kegiatan, atau informasi lain yang dipublikasikan melalui website.

> Catatan: Pada dokumen PDF, deskripsi tabel `news` tampaknya tertukar dengan deskripsi tabel `events`. Berdasarkan struktur database final, tabel `news` lebih tepat digunakan untuk menyimpan konten berita, sedangkan tabel `events` digunakan untuk menyimpan data event/tour.

### Struktur Tabel `news`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID berita. |
| `created_by` | integer | Foreign Key ke `users.id` | Admin yang membuat berita. |
| `slug` | varchar | Not Null, Unique | Slug URL berita. |
| `title` | varchar | Not Null | Judul berita. |
| `thumbnail` | varchar | Not Null | Gambar thumbnail berita. |
| `content` | text | Not Null | Isi berita. |
| `status` | enum | Default: `draft` | Status publikasi berita. |
| `created_at` | timestamp | - | Waktu data dibuat. |
| `deleted_at` | timestamp | - | Waktu data dihapus secara soft delete. |

---

## 6. Tabel `events`

Tabel `events` berfungsi untuk menyimpan data event atau tour yang akan dilakukan oleh grup band Newjeans.

Penggunaan data event pada website adalah:

- Halaman **schedules**
- Halaman **schedule detail**

Data event mencakup judul acara, venue, kota, tanggal acara, poster, dan deskripsi acara.

### Struktur Tabel `events`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID event. |
| `created_by` | integer | Foreign Key ke `users.id` | Admin yang membuat event. |
| `slug` | varchar | Not Null, Unique | Slug URL event. |
| `title` | varchar | Not Null | Judul event. |
| `venue` | varchar | Not Null | Tempat pelaksanaan event. |
| `city` | varchar | Not Null | Kota pelaksanaan event. |
| `event_date` | datetime | - | Tanggal dan waktu event. |
| `poster` | varchar | - | Gambar poster event. |
| `description` | text | Not Null | Deskripsi event. |
| `created_at` | timestamp | - | Waktu data dibuat. |
| `deleted_at` | timestamp | - | Waktu data dihapus secara soft delete. |

---

## 7. Tabel `members`

Tabel `members` berfungsi untuk menyimpan data member dari grup band Newjeans.

Penggunaan data member pada website adalah pada halaman **about**, khususnya pada section **member profiles**.

### Struktur Tabel `members`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID member. |
| `created_by` | integer | Foreign Key ke `users.id` | Admin yang menambahkan data member. |
| `name` | varchar | Not Null | Nama member. |
| `photo` | varchar | Not Null | Foto member. |
| `description` | varchar | Not Null | Deskripsi singkat member. |
| `member_roles` | varchar | Not Null | Peran atau posisi member. |
| `created_at` | timestamp | - | Waktu data dibuat. |
| `deleted_at` | timestamp | - | Waktu data dihapus secara soft delete. |

---

## 8. Tabel `histories`

Tabel `histories` berfungsi untuk menyimpan data histori atau riwayat perjalanan grup band Newjeans.

Penggunaan data histories pada website adalah pada halaman **about**.

### Struktur Tabel `histories`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID histori. |
| `created_by` | integer | Foreign Key ke `users.id` | Admin yang menambahkan histori. |
| `image` | varchar | Not Null | Gambar pendukung histori. |
| `title` | varchar | Not Null | Judul histori. |
| `description` | varchar | Not Null | Deskripsi histori. |
| `event_date` | datetime | - | Tanggal kejadian histori. |
| `created_at` | timestamp | - | Waktu data dibuat. |
| `deleted_at` | timestamp | - | Waktu data dihapus secara soft delete. |

---

## 9. Tabel `gallery_items`

Tabel `gallery_items` berfungsi untuk menyimpan data galeri dari grup band Newjeans.

Penggunaan data galeri pada website adalah:

- Halaman **gallery**
- Section **gallery** pada halaman **home**

### Struktur Tabel `gallery_items`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID item galeri. |
| `created_by` | integer | Foreign Key ke `users.id` | Admin yang menambahkan gambar. |
| `sort_order` | integer | Default: 0 | Urutan tampilan gambar. |
| `image` | varchar | Not Null | Path atau URL gambar. |
| `created_at` | timestamp | - | Waktu data dibuat. |
| `deleted_at` | timestamp | - | Waktu data dihapus secara soft delete. |

---

## Fitur Pembelian Album

Selain tabel utama untuk konten website, kelompok kami juga menambahkan struktur database untuk fitur pembelian album. Fitur ini mencakup proses pemesanan, keranjang belanja, pengiriman, pembayaran, dan riwayat status pesanan.

Tabel yang ditambahkan untuk fitur ini adalah:

1. `shipping_options`
2. `carts`
3. `cart_items`
4. `orders`
5. `order_items`
6. `payment_proofs`
7. `order_status_histories`

---

## 10. Tabel `shipping_options`

Tabel `shipping_options` berfungsi untuk menyimpan tipe pengiriman yang tersedia.

Rencananya, hanya akan dibuat dua tipe pengiriman, yaitu:

| Tipe Pengiriman | Estimasi |
|---|---|
| Reguler | 7 hari |
| Express | 3 hari |

### Struktur Tabel `shipping_options`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID opsi pengiriman. |
| `name` | varchar | Not Null, Unique | Nama opsi pengiriman, misalnya Reguler atau Express. |
| `fee` | decimal(10,2) | Not Null, Default: 0 | Biaya pengiriman dalam USD. |
| `estimate_text` | varchar | - | Teks estimasi pengiriman untuk simulasi. |
| `is_active` | boolean | Not Null, Default: true | Menentukan apakah opsi pengiriman aktif. |
| `created_at` | timestamp | - | Waktu data dibuat. |

---

## 11. Tabel `carts`

Tabel `carts` berfungsi untuk menyimpan keranjang belanja milik pengguna. Setiap pengguna memiliki satu keranjang.

Relasi antara tabel `users` dan tabel `carts` adalah **1:1** berdasarkan kolom `user_id` yang bersifat unique. Namun, secara logika bisnis, satu keranjang dapat memiliki banyak item melalui tabel `cart_items`.

### Struktur Tabel `carts`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID keranjang. |
| `user_id` | integer | Foreign Key ke `users.id`, Not Null, Unique | ID pengguna pemilik keranjang. |
| `created_at` | timestamp | - | Waktu keranjang dibuat. |

---

## 12. Tabel `cart_items`

Tabel `cart_items` berfungsi untuk menampung album-album yang dimasukkan oleh customer ke dalam keranjang.

Setiap item keranjang memiliki relasi ke tabel `carts` dan tabel `albums`. Kolom `qty` digunakan untuk menyimpan jumlah album yang dimasukkan ke dalam keranjang.

### Struktur Tabel `cart_items`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID item keranjang. |
| `cart_id` | integer | Foreign Key ke `carts.id`, Not Null | Relasi ke keranjang. |
| `album_id` | integer | Foreign Key ke `albums.id`, Not Null | Relasi ke album. |
| `qty` | integer | Not Null, Default: 1 | Jumlah item album dalam keranjang. |
| `created_at` | timestamp | - | Waktu item ditambahkan ke keranjang. |

---

## 13. Tabel `orders`

Tabel `orders` berfungsi untuk menyimpan data pesanan yang dibuat oleh customer saat melakukan checkout.

Setiap pesanan memiliki nomor invoice unik, data pengguna, opsi pengiriman, alamat pengiriman, subtotal, biaya pengiriman, total pembayaran, dan status pesanan.

Alamat pengiriman disimpan sebagai snapshot saat checkout, sehingga perubahan alamat pengguna di masa depan tidak mengubah data pesanan lama.

### Struktur Tabel `orders`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID pesanan. |
| `invoice_number` | varchar | Not Null, Unique | Nomor invoice pesanan. |
| `user_id` | integer | Foreign Key ke `users.id`, Not Null | Pengguna yang membuat pesanan. |
| `shipping_option_id` | integer | Foreign Key ke `shipping_options.id`, Not Null | Opsi pengiriman yang dipilih. |
| `address` | text | Not Null | Alamat pengiriman snapshot saat checkout. |
| `subtotal` | decimal(10,2) | Not Null | Total harga item sebelum ongkos kirim. |
| `shipping_cost` | decimal(10,2) | Not Null | Biaya pengiriman. |
| `total` | decimal(10,2) | Not Null | Total pembayaran akhir. |
| `status` | order_status | Not Null, Default: `PENDING_PAYMENT` | Status pesanan. |
| `paid_at` | timestamp | - | Waktu pembayaran dilakukan. |
| `completed_at` | timestamp | - | Waktu pesanan diselesaikan. |
| `cancelled_at` | timestamp | - | Waktu pesanan dibatalkan. |
| `created_at` | timestamp | - | Waktu pesanan dibuat. |

---

## 14. Tabel `order_items`

Tabel `order_items` berfungsi untuk menyimpan detail item yang ada di dalam sebuah pesanan.

Setiap item pesanan menyimpan snapshot nama album dan harga album pada saat transaksi. Hal ini penting agar riwayat pesanan tetap akurat meskipun data album berubah di kemudian hari.

### Struktur Tabel `order_items`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID item pesanan. |
| `order_id` | integer | Foreign Key ke `orders.id`, Not Null | Relasi ke pesanan. |
| `album_id` | integer | Foreign Key ke `albums.id`, Not Null | Relasi ke album yang dibeli. |
| `album_name` | varchar | Not Null | Snapshot nama album saat checkout. |
| `price` | decimal(10,2) | Not Null | Snapshot harga album dalam USD saat checkout. |
| `qty` | integer | Not Null | Jumlah album yang dibeli. |
| `subtotal` | decimal(10,2) | Not Null | Total harga per item. |
| `created_at` | timestamp | - | Waktu item pesanan dibuat. |

---

## 15. Tabel `payment_proofs`

Tabel `payment_proofs` berfungsi untuk menyimpan bukti pembayaran yang diunggah oleh customer.

Admin dapat melakukan verifikasi terhadap bukti pembayaran yang masuk. Jika bukti pembayaran ditolak, admin dapat menambahkan catatan penolakan pada kolom `reject_note`.

### Struktur Tabel `payment_proofs`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID bukti pembayaran. |
| `order_id` | integer | Foreign Key ke `orders.id`, Not Null | Relasi ke pesanan. |
| `file_path` | varchar | Not Null | Path file bukti pembayaran. |
| `status` | proof_status | Not Null, Default: `PENDING` | Status verifikasi bukti pembayaran. |
| `reject_note` | varchar | - | Catatan jika bukti pembayaran ditolak. |
| `reviewed_by` | integer | Foreign Key ke `users.id` | Admin yang memverifikasi. |
| `reviewed_at` | timestamp | - | Waktu bukti diverifikasi. |
| `created_at` | timestamp | - | Waktu bukti pembayaran diunggah. |

---

## 16. Tabel `order_status_histories`

Tabel `order_status_histories` berfungsi untuk menyimpan riwayat perubahan status pesanan.

Dengan tabel ini, setiap perubahan status pesanan dapat dilacak, misalnya dari `PENDING_PAYMENT` menjadi `WAITING_APPROVAL`, lalu menjadi `COMPLETED` atau `CANCELLED`.

### Struktur Tabel `order_status_histories`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | integer | Primary Key, Increment, Not Null | ID riwayat status. |
| `order_id` | integer | Foreign Key ke `orders.id`, Not Null | Relasi ke pesanan. |
| `status` | order_status | Not Null | Status pesanan. |
| `note` | varchar | - | Catatan perubahan status. |
| `created_at` | timestamp | - | Waktu perubahan status terjadi. |

---

## Ringkasan Relasi Antar Tabel

Berikut adalah ringkasan relasi antar tabel dalam database ini.

### Relasi Utama Konten Website

| Tabel Asal | Kolom | Relasi Ke Tabel | Kolom Tujuan | Jenis Relasi |
|---|---|---|---|---|
| `albums` | `album_type_id` | `album_types` | `id` | Banyak ke satu |
| `songs` | `album_id` | `albums` | `id` | Banyak ke satu |
| `news` | `created_by` | `users` | `id` | Banyak ke satu |
| `events` | `created_by` | `users` | `id` | Banyak ke satu |
| `members` | `created_by` | `users` | `id` | Banyak ke satu |
| `histories` | `created_by` | `users` | `id` | Banyak ke satu |
| `gallery_items` | `created_by` | `users` | `id` | Banyak ke satu |

### Relasi Fitur Pembelian Album

| Tabel Asal | Kolom | Relasi Ke Tabel | Kolom Tujuan | Jenis Relasi |
|---|---|---|---|---|
| `carts` | `user_id` | `users` | `id` | Satu ke satu |
| `cart_items` | `cart_id` | `carts` | `id` | Banyak ke satu |
| `cart_items` | `album_id` | `albums` | `id` | Banyak ke satu |
| `orders` | `user_id` | `users` | `id` | Banyak ke satu |
| `orders` | `shipping_option_id` | `shipping_options` | `id` | Banyak ke satu |
| `order_items` | `order_id` | `orders` | `id` | Banyak ke satu |
| `order_items` | `album_id` | `albums` | `id` | Banyak ke satu |
| `payment_proofs` | `order_id` | `orders` | `id` | Banyak ke satu |
| `payment_proofs` | `reviewed_by` | `users` | `id` | Banyak ke satu |
| `order_status_histories` | `order_id` | `orders` | `id` | Banyak ke satu |

---

## Alur Fitur Pembelian Album

Berikut adalah gambaran alur pembelian album berdasarkan struktur database yang dirancang.

1. Customer memilih album yang ingin dibeli.
2. Album dimasukkan ke dalam keranjang melalui tabel `cart_items`.
3. Customer melakukan checkout.
4. Sistem membuat data pesanan pada tabel `orders`.
5. Detail pesanan disimpan pada tabel `order_items`.
6. Status awal pesanan adalah `PENDING_PAYMENT`.
7. Customer mengunggah bukti pembayaran ke tabel `payment_proofs`.
8. Admin melakukan verifikasi bukti pembayaran.
9. Jika bukti pembayaran disetujui, status pesanan dapat berubah menjadi `COMPLETED`.
10. Jika bukti pembayaran ditolak, admin dapat mengisi `reject_note`.
11. Setiap perubahan status pesanan dicatat pada tabel `order_status_histories`.

---

## Penutup

Rancangan akhir basis data Newjeans Band ini mencakup tabel-tabel untuk kebutuhan konten website dan fitur pembelian album. Revisi yang telah dilakukan meliputi penyederhanaan timestamp menggunakan `created_at` dan `deleted_at`, penambahan kolom `release_date` pada tabel `albums`, serta penambahan struktur tabel untuk fitur e-commerce sederhana.

Dengan struktur database ini, website Newjeans Band dapat mengelola data album, lagu, event, berita, member, histori, galeri, serta mendukung proses pembelian album oleh customer.