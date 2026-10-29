# design.md

Panduan desain bergaya album debut *NewJeans*: latar putih, satu warna biru, tekstur dot kecil, dan stiker playful. Dipakai sebagai acuan untuk seluruh halaman fan hub: `index.html`, `discography.html`, `schedule.html`, `web-page/about.html`, dan `gallery-project/index.html`.

## Catatan pribadi

- **Suka font tebal dan miring (bold italic).** Gaya ini jadi karakter utama untuk judul, logo, dan teks yang ingin ditonjolkan.
- Latar harus **putih**, warna utama **biru #005bab**, dan teksturnya **dot kecil**, bukan garis atau grain kasar.

## Konsep

"NewJeans are not blue": satu warna biru dipakai penuh, tetapi suasananya ceria. Kontras utama ada di **dasar yang bersih dan dingin** (putih, biru, dot) melawan **aksen kecil yang hangat dan mengilap** (stiker).

## Warna

| Token | Hex | Pemakaian |
|---|---|---|
| `--paper` | `#ffffff` | Latar halaman dan kartu |
| `--blue` | `#005bab` | Warna utama: hero, judul, border, tombol, footer |
| `--blue-deep` | `#003a6e` | Bayangan duotone, hover gelap |
| `--blue-light` | `#8fbbe6` | Midtone terang duotone |
| `--blue-tint` | `#eef5fc` | Highlight, latar tipis |
| `--ink` | `#0a2a4d` | Teks isi |
| `--muted` | `#3d6a94` | Teks sekunder |
| `--silver` | `#d4e3f2` | Stiker metalik, pita label |
| `--pink` | `#ff7eb6` | Aksen stiker |
| `--lime` | `#b8f24c` | Aksen stiker, kata penekanan |

Aturan: biru dan putih mendominasi sekitar 90% tampilan. Pink dan lime hanya muncul sebagai stiker kecil.

## Tekstur dot

Dot kecil dan rapat, seperti halftone cetak.

```css
/* di atas area biru */
background-image: radial-gradient(circle, rgba(255,255,255,.4) .7px, transparent 1px);
background-size: 4px 4px;

/* di atas latar putih, sangat tipis */
background-image: radial-gradient(circle, rgba(0,91,171,.14) .7px, transparent 1px);
background-size: 5px 5px;
```

- Ukuran dot 4 sampai 5px. Jangan lebih besar.
- Satu jenis tekstur saja per area. Jangan campur dengan garis atau noise.

## Tipografi

Gaya favorit: **tebal dan miring**.

| Peran | Font | Gaya |
|---|---|---|
| Judul besar dan logo | Archivo (900) | **Bold italic**, rapat, huruf besar atau campur |
| Judul bagian | Archivo (800) | **Bold italic** |
| Aksen playful | Bagel Fat One | Bubble, dipakai sedikit |
| Aksen futuristik | Orbitron 800 | Gaya chrome, hanya untuk logo |
| Isi teks | DM Sans 400/500 | Normal, 16 sampai 17px |

```css
.display {
  font-family: "Archivo", "Arial Black", sans-serif;
  font-weight: 900;
  font-style: italic;
  letter-spacing: -.02em;
  line-height: 1;
}
```

Aturan:
- Gunakan italic asli (`font-style: italic` dengan font yang punya varian italic), bukan `skewX`, supaya bentuk huruf tetap rapi.
- Teks isi tetap normal agar mudah dibaca. Tebal-miring hanya untuk judul, label, dan logo.
- Panjang baris isi maksimal sekitar 68 karakter.
- Logo boleh berganti gaya (bubble, chrome, script, bold italic), tetapi nama tetap terbaca.

Google Fonts:
```html
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:ital,wght@0,400;1,800;1,900&family=Bagel+Fat+One&family=DM+Sans:wght@400;500;700&display=swap">
```

## Layout

- Lebar konten maksimal 1000px, padding samping 22px.
- Hero penuh warna biru dengan dot, judul besar bold italic di kiri.
- Bagian berikutnya di atas putih, dipisah dengan jarak (54px), bukan garis.
- Kartu: border 2px biru, radius 22px, sudut dibuat bervariasi (misalnya satu sudut 4px) agar tidak terasa seragam.
- Tata letak responsif: grid `auto-fit` dengan lebar minimum 260px.

## Komponen

- **Kartu:** latar putih, border biru, judul biru bold italic.
- **Stiker:** bentuk bulat atau rounded, warna pink, lime, atau perak, diputar -8° sampai 8°, dengan bayangan solid tipis. Saat hover, kembali lurus dan sedikit membesar.
- **Pita label:** kotak perak kecil, teks tebal, miring sedikit (-1.5°), dipakai untuk slogan pendek.
- **Tabel:** header biru, isi putih, garis pemisah `--silver`.
- **Tombol:** latar biru, teks putih bold italic, radius penuh.

## Komposisi

- Close-up besar dan area utama dibiarkan lega. Stiker ditumpuk di tepi dan sudut, tidak menutup fokus.
- Penempatan tidak simetris tetapi tetap seimbang.
- Jaga sistem: warna, tekstur, dan struktur tetap sama; yang divariasikan adalah posisi stiker dan gaya logo.

## Gerak

- Hemat. Cukup efek hover pada stiker dan tombol.
- Hormati `prefers-reduced-motion`.

## Hindari

- Warna lain selain palet di atas sebagai warna dominan.
- Dot besar atau tekstur kasar.
- Teks isi panjang dalam bold italic.
- Terlalu banyak stiker sampai terlihat ramai di layar kecil.
- Mode gelap (latar harus tetap putih).

## Cek akhir

- [ ] Latar putih, biru tepat `#005bab`
- [ ] Dot kecil (4 sampai 5px) dan halus
- [ ] Judul dan logo tebal-miring
- [ ] Stiker hanya sebagai aksen
- [ ] Nyaman di ponsel (tidak ada scroll horizontal)
