# NewJeans Fan Hub

Website fan-made bertema NewJeans: beranda, diskografi, jadwal, profil member, galeri, dan berita. Proyek ini berupa static site (HTML, CSS, JavaScript) tanpa build step.

## Halaman

| Halaman | File | URL lokal |
|---|---|---|
| Home | `index.html` | `/` |
| Discography | `pages/discography.html` | `/pages/discography.html` |
| Schedule | `pages/schedule.html` | `/pages/schedule.html` |
| News | `pages/news.html` | `/pages/news.html` |
| About & members | `pages/about.html` | `/pages/about.html` |
| Gallery | `pages/gallery.html` | `/pages/gallery.html` |

Hanya `index.html` yang berada di root. Semua halaman lain berada di `pages/`, dan seluruh aset frontend berada di `assets/`.

## Struktur folder

```text
.
├── index.html
├── pages/
│   ├── about.html
│   ├── discography.html
│   ├── gallery.html
│   ├── news.html
│   └── schedule.html
├── assets/
│   ├── audio/            # trek untuk carousel
│   ├── css/              # theme.css + CSS per halaman
│   ├── data/
│   │   └── news.json     # data frontend highlight news
│   ├── images/
│   │   ├── albums/       # cover album dan CD
│   │   ├── brand/        # logo dan favicon
│   │   ├── gallery/      # 12 foto galeri (satu-satunya sumber)
│   │   └── members/      # foto member
│   ├── js/               # theme.js, komponen, data, dan JS per halaman
│   ├── vendor/
│   │   └── locomotive-scroll/
│   └── videos/           # video hero
├── archive/
│   ├── legacy/
│   │   └── scroll-demo/  # demo Locomotive lama
│   ├── unused-assets/    # aset lama yang tidak dipakai
│   └── README.md
├── docs/
│   ├── design.md         # panduan desain
│   ├── reports/          # laporan proyek
│   └── superpowers/      # catatan perencanaan
└── README.md
```

## Menjalankan lokal

Home dan News mengambil data dari `assets/data/news.json`, jadi halaman tersebut perlu dibuka lewat static server, bukan langsung melalui `file://`. Jalankan server dari root proyek:

```powershell
python -m http.server 8000
```

Lalu buka `http://localhost:8000`.

### Locomotive Scroll

Project memakai Locomotive Scroll **v5.0.0** dari `assets/vendor/locomotive-scroll/` pada semua halaman utama. API v5 memakai Lenis dan tidak membutuhkan `data-scroll-container` atau `data-scroll-section`.
Elemen `.reveal` memakai deteksi viewport Locomotive saat smooth scroll aktif; observer native hanya menjadi fallback.

- Demo arsip: `http://localhost:8000/archive/legacy/scroll-demo/scroll-demo.html`
- Jika browser melaporkan reduced motion, tambahkan `?smooth=1` untuk mengaktifkan smooth scroll secara eksplisit di halaman tersebut.
- Tambahkan `&scrollDebug=1` untuk melihat event scroll di console.

## Konvensi aset

- Nama file memakai huruf kecil dan tanda hubung, tanpa spasi atau tanda kurung.
- Semua aset frontend berada di `assets/`: CSS di `assets/css/`, JavaScript di `assets/js/`, gambar di `assets/images/`, audio di `assets/audio/`, video di `assets/videos/`, dan data di `assets/data/`.
- Cover album masuk ke `assets/images/albums/`, foto member ke `assets/images/members/`, logo dan favicon ke `assets/images/brand/`.
- Foto galeri hanya disimpan di `assets/images/gallery/` dan dipakai bersama oleh Home dan Gallery.
- Aset yang tidak lagi dipakai dipindahkan ke `archive/unused-assets/`, bukan dihapus.

## Dokumentasi

- [docs/design.md](docs/design.md): warna, tipografi, layout, dan aturan komponen.
- [docs/reports/Gallery_Web_Page_Report_Gilang_3202416074.pdf](docs/reports/Gallery_Web_Page_Report_Gilang_3202416074.pdf): laporan proyek galeri.
