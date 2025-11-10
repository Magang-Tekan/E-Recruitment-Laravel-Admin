# Instruksi Penggunaan Header dan Footer untuk Export PDF

## Lokasi File
Folder ini digunakan untuk menyimpan gambar header dan footer yang akan digunakan pada export PDF data kandidat.

## Nama File Berdasarkan Company

Sistem akan otomatis memilih header dan footer berdasarkan company yang dilamar oleh kandidat:

### PT Mitra Karya Analitika (MIKA)
- **Header**: `header_mika.png` (atau `header_mika.jpg`, `header_mika.jpeg`, `header_mika.gif`)
- **Footer**: `footer_mika.png` (atau `footer_mika.jpg`, `footer_mika.jpeg`, `footer_mika.gif`)

### PT Autentik Karya Analitika
- **Header**: `header_autentik.png` (atau `header_autentik.jpg`, `header_autentik.jpeg`, `header_autentik.gif`)
- **Footer**: `footer_autentik.png` (atau `footer_autentik.jpg`, `footer_autentik.jpeg`, `footer_autentik.gif`)

### Default (Fallback)
- **Header**: `header.png` (atau `header.jpg`, `header.jpeg`, `header.gif`)
- **Footer**: `footer.png` (atau `footer.jpg`, `footer.jpeg`, `footer.gif`)

## Format yang Didukung
- PNG (disarankan)
- JPG/JPEG
- GIF

## Cara Menggunakan
1. Simpan gambar header dengan nama sesuai company (contoh: `header_mika.png` atau `header_autentik.png`)
2. Simpan gambar footer dengan nama sesuai company (contoh: `footer_mika.png` atau `footer_autentik.png`)
3. Pastikan gambar memiliki resolusi yang cukup untuk tampilan PDF (disarankan minimal 1200px lebar untuk header)
4. Sistem akan otomatis mendeteksi company dan menggunakan gambar yang sesuai

## Logika Pemilihan
Sistem akan mencari header/footer dengan urutan prioritas:
1. Header/Footer spesifik company (misal: `header_autentik.png` untuk Autentik Karya Analitika)
2. Header/Footer default (`header.png`, `footer.png`)

## Catatan
- Jika gambar tidak ditemukan, sistem akan menggunakan header dan footer default (teks)
- Gambar akan otomatis di-resize sesuai lebar halaman PDF
- Pastikan ukuran file tidak terlalu besar untuk performa yang optimal
- Nama company akan dicocokkan secara case-insensitive (tidak membedakan huruf besar/kecil)

