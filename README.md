# Material Monitoring & K-RAB Konstruksi

Aplikasi enterprise berbasis **Laravel** untuk mengelola siklus perencanaan Rencana Anggaran Biaya (RAB) proyek konstruksi, breakdown material dasar (*Bill of Materials* / BOM), pengadaan fisik (*Surat Jalan / DO* oleh Pengawas Lapangan), administrasi finansial (*Faktur Invoice* oleh Purchasing), validasi deviasi ganda (*Dual Approval*), manajemen armada alat berat & mesin (*Fast Bulk Input*), perbandingan realisasi anggaran (RAB vs Aktual), serta pusat peringatan deviasi (*Alert Center*).

---

## 🚀 Fitur Utama Sistem

| # | Modul Sistem | Deskripsi & Fungsionalitas |
|---|---|---|
| 1 | **RAB Tree Berjenjang 5 Level** | Hierarki interaktif Level 1 (Kategori), Level 2 (Sub), Level 3 (Sub-Sub), Level 4 (Item Pekerjaan), Level 5 (Material Breakdown BOM). Penomoran auto-generate standar BQ (*I*, *I.A*, *A.1*, *1.0*, *- Material*). |
| 2 | **Material Breakdown (BOM)** | Memecah item pekerjaan (misal: *Kolom K1* atau *Keramik 25x20*) menjadi material dasar (ready mix beton, semen, besi beton, bekisting) dengan fitur duplikasi BOM cepat. |
| 3 | **Pemisahan Form PO, DO, & Invoice** | Alur pengadaan dipisahkan secara independen untuk menjaga pemisahan tugas (*segregation of duties*):<br>• **Purchase Order (PO):** Pemesanan barang ke rekanan supplier.<br>• **Surat Jalan (DO):** Penerimaan fisik barang di lapangan dengan upload bukti surat jalan.<br>• **Faktur Tagihan (Invoice):** Pencatatan tagihan dan nominal invoice dari vendor supplier. |
| 4 | **Hak Akses Modular & Granular (15 Modul)** | Pengaturan izin per modul dengan pemisahan *Lihat Saja (Read)* vs *Buat & Kelola (Write)* untuk PO, DO, dan Invoice. Modal rincian hak akses dengan checklist interaktif. |
| 5 | **Validasi Ganda (Dual Approval)** | Status deviasi material tervalidasi penuh (*Fully Validated*) hanya jika Pengawas Lapangan (Surat Jalan DO) **DAN** Purchasing (Faktur Tagihan) keduanya telah memverifikasi. |
| 6 | **Alat & Mesin Fast Bulk Input** | Katalog master alat proyek (Excavator, Crane, Genset, Molen, Scaffolding, armada truk) dengan form alokasi massal cepat ke proyek. |
| 7 | **Kalkulator Dimensi (M² & M³)** | Tool kalkulator interaktif untuk menghitung Luas ($P \times L$) dan Volume ($P \times L \times T$) yang langsung disalin ke input kuantiti RAB. |
| 8 | **Pencatatan Biaya Realisasi Terpisah** | Tabel realisasi biaya terpisah dari baseline anggaran RAB untuk menjaga integritas data kontrak proyek. |
| 9 | **Pusat Peringatan Deviasi (Alert Engine)** | Pemantauan otomatis selisih volume dan biaya aktual terhadap batas toleransi (+10% / -5%) dengan badge tingkat urgensi (*Critical*, *Warning*, *Info*). |
| 10 | **Template Excel Baku & Impor Akurat** | Template impor 2 sheet (*Info Proyek* & *Detail RAB*) dengan parser validasi berlapis dan pratinjau sebelum disimpan ke database. |

---

## 🛠️ Persyaratan Sistem

- **PHP** >= 8.2 (ekstensi: `pdo_sqlite` atau `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`)
- **Composer** >= 2.0
- **Node.js** >= 18 & **NPM**
- **Database:** SQLite (default) atau MySQL / PostgreSQL

---

## 📦 Panduan Instalasi

1. **Clone Repositori:**
   ```bash
   git clone https://github.com/hanzputram/Material-Monitoring.git
   cd Material-Monitoring
   ```

2. **Instal Dependensi PHP:**
   ```bash
   composer install
   ```

3. **Instal Dependensi Frontend & Build Assets:**
   ```bash
   npm install
   npm run build
   ```

4. **Konfigurasi Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Migrasi Database & Seeder:**
   ```bash
   touch database/database.sqlite   # Jika menggunakan SQLite
   php artisan migrate --seed
   ```

6. **Jalankan Server Lokal:**
   ```bash
   php artisan serve
   ```
   Buka peramban pada alamat: `http://127.0.0.1:8000`

---

## 👥 Manajemen Pengguna & Peran

Sistem mendukung peran berbasis tugas kerja:
- **Super Administrator:** Otoritas penuh mengelola akun staf dan konfigurasi modular 15 permission.
- **Project Manager:** Perencanaan proyek, penyusunan RAB 5 Level, monitoring biaya & pengadaan.
- **Pengawas Lapangan:** Pencatatan dan upload Surat Jalan (DO), verifikasi fisik penerimaan material & alat.
- **Purchasing Officer:** Pemecahan BOM material, pembuatan PO supplier, input tagihan invoice.
- **Finance / Direksi:** Monitoring deviasi realisasi biaya vs RAB baseline dan eksekutif report.

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah lisensi privat / proprietary untuk kebutuhan manajemen proyek konstruksi.
