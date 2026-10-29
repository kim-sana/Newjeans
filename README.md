# NewJeans Fan Hub

Website fan-made bertema NewJeans: beranda, diskografi, jadwal, profil member, dan galeri. Proyek ini berupa static site (HTML, CSS, JavaScript) tanpa build step.

## Halaman

| Halaman | File | URL lokal |
|---|---|---|
| Home | `index.html` | `/index.html` |
| Discography | `discography.html` | `/discography.html` |
| Schedule | `schedule.html` | `/schedule.html` |
| About & members | `web-page/about.html` | `/web-page/about.html` |
| Gallery | `gallery-project/index.html` | `/gallery-project/index.html` |
| Scroll demo | `scroll-demo.html` | `/scroll-demo.html` |

Folder `web-page/` dan `gallery-project/` dipertahankan agar URL halaman yang sudah ada tetap valid.

## Struktur folder

```text
.
├── index.html
├── discography.html
├── schedule.html
├── web-page/
│   └── about.html
├── gallery-project/
│   └── index.html
├── css/                  # theme.css + CSS per halaman
├── js/                   # theme.js, carousel, dan JS per halaman
├── images/
│   ├── albums/           # cover album dan CD
│   ├── brand/            # logo dan favicon
│   ├── gallery/          # 12 foto galeri (satu-satunya sumber)
│   └── members/          # foto member
├── audio/                # trek untuk carousel
├── videos/               # video hero
├── docs/
│   ├── design.md         # panduan desain
│   └── reports/          # laporan proyek
└── archive/
    └── unused-assets/    # aset lama yang tidak dipakai
```

## Menjalankan lokal

Buka `index.html` langsung di browser, atau jalankan static server dari root proyek:

```powershell
python -m http.server 8000
```

Lalu buka `http://localhost:8000`.

### Locomotive Scroll

Project memakai Locomotive Scroll **v5.0.0** dari `vendor/locomotive-scroll/` pada semua halaman utama. API v5 memakai Lenis dan tidak membutuhkan `data-scroll-container` atau `data-scroll-section`.
Elemen `.reveal` memakai deteksi viewport Locomotive saat smooth scroll aktif; observer native hanya menjadi fallback.

- Demo: `http://localhost:8000/scroll-demo.html`
- Jika browser melaporkan reduced motion, tambahkan `?smooth=1` untuk mengaktifkan smooth scroll secara eksplisit di halaman tersebut.
- Tambahkan `&scrollDebug=1` untuk melihat event scroll di console.

## Konvensi aset

- Nama file memakai huruf kecil dan tanda hubung, tanpa spasi atau tanda kurung.
- Cover album masuk ke `images/albums/`, foto member ke `images/members/`, logo dan favicon ke `images/brand/`.
- Foto galeri hanya disimpan di `images/gallery/` dan dipakai bersama oleh Home dan Gallery.
- Aset yang tidak lagi dipakai dipindahkan ke `archive/unused-assets/`, bukan dihapus.

## Dokumentasi

- [docs/design.md](docs/design.md): warna, tipografi, layout, dan aturan komponen.
- [docs/reports/Gallery_Web_Page_Report_Gilang_3202416074.pdf](docs/reports/Gallery_Web_Page_Report_Gilang_3202416074.pdf): laporan proyek galeri.
