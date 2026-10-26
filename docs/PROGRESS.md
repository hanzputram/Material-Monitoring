# Project Progress & Memory: Material-Monitoring

## Status Terakhir (2026-10-26)
- **Status Sesi**: Implementasi optimasi tampilan antarmuka responsif di semua perangkat (Mobile, Tablet, Desktop) selesai dan 100% terverifikasi.
- **Hasil Akhir Test Suite**: `php artisan test --compact` -> **64 tests passed, 294 assertions** (0 failed, 0 regressions).
- **Hasil Akhir Asset Build**: `npm run build` -> Vite v8 production build sukses (1.28s).
- **Code Style**: `vendor/bin/pint --format agent` -> 100% passed.

## Overhaul Responsif Antarmuka Lintas Perangkat (Mobile, Tablet, Desktop)
- **CSS Foundation (`resources/css/app.css`)**:
  - Pencegahan auto-zoom form pada iOS/Safari: standardisasi ukuran `font-size: 16px !important` untuk seluruh elemen input/select/textarea pada layar `< 640px`.
  - Peningkatan touch target size: minimal tinggi tombol dan link aksi `42px` untuk kenyamanan tap jempol di mobile.
  - Safe area clearance: penambahan padding bawah `padding-bottom: 5.5rem` di mobile & tablet agar konten dan tombol form tidak tertutup navigasi bawah.
  - Smooth touch scroll: penambahan `-webkit-overflow-scrolling: touch` dan `overscroll-behavior-x: contain` pada kontainer tabel.
  - Standardisasi modal responsif: pembatasan tinggi `max-h-[90vh] overflow-y-auto` dan lebar `max-w-[calc(100vw-1rem)]`.
- **Master Layout (`resources/views/layouts/app.blade.php`)**:
  - Penambahan backdrop lock dan penutup drawer sidebar otomatis saat klik luar / tekan ESC.
  - Mobile Bottom Navigation Bar: bilah menu cepat di bagian bawah layar mobile (Dashboard, RAB, Pengadaan, Biaya, Menu Lengkap) berlatar blur glassmorphism dan aman untuk notch/home indicator (`env(safe-area-inset-bottom)`).
- **Tabel & Kontainer Data Lintas Modul**:
  - Penetapan `min-w-[...]` dan pembungkus `overflow-x-auto` pada seluruh tabel data (PO, DO, Surat Jalan, Invoices, Pembayaran, RAB Tree, Material, Pekerja, Alat Berat, Varians Approval, User Management) sehingga kolom data tidak tertekan/terpotong pada layar kecil (360px - 414px).
  - Tab Switcher responsif dengan class `.tab-scroll-container` (`overflow-x-auto whitespace-nowrap`) pada halaman Pengadaan, Material, Alat Berat, dan Pekerja.
- **Formulir & Modal Dialog**:
  - Transformasi form input repeater (PO, DO, Payment, RAB Items, BOM Breakdown) dari layout kaku multi-kolom menjadi responsif (`grid-cols-1 sm:grid-cols-2` dan `col-span-12 sm:col-span-...`) sehingga kolom kuantiti dan harga tidak berjejalan.
  - Seluruh modal dialog kini memiliki `max-h-[90vh] overflow-y-auto` agar tombol Aksi/Simpan tetap dapat diakses saat keyboard virtual mobile muncul.

## Patch Keamanan PHP2Shell & Pembersihan Data Dummy
- **PHP 8.5.12**: Upgrade runtime PHP dari 8.5.10 ke 8.5.12 via Scoop untuk mitigasi CVE-2026-109618 & CVE-2026-109619.
- **php.ini Hardening**: Menonaktifkan `session.upload_progress.enabled = Off` pada konfigurasi PHP aktif.
- **Pembersihan Database Dummy**: Menghapus seluruh record proyek sample & transaksi dummy (proyek, RAB, PO, DO, invoice, realisasi, alert, alokasi pekerja/alat, pembayaran). Mempertahankan data master (satuan, katalog material, katalog alat, role & user).
- **DatabaseSeeder**: Menonaktifkan pemanggilan `SampleProjectSeeder` agar data dummy tidak terisi kembali saat `db:seed` dijalankan.

## Perintah Penting
- **Dev**: `composer run dev`
- **Test**: `php artisan test --compact`
- **Build**: `npm run build`
- **Lint / Format**: `vendor/bin/pint --format agent`

## Penghapusan Modul Down Payment (DP)
- **Tabel & Database**: Migrasi `2026_10_26_020513_drop_purchase_down_payments_and_columns_from_invoices` menghapus tabel `purchase_down_payments` serta kolom `down_payment_id` dan `down_payment_amount` dari tabel `invoices`.
- **Model**:
  - Dihapus: `app/Models/PurchaseDownPayment.php`.
  - [Invoice](file:///c:/Users/itpta/material-monitoring/app/Models/Invoice.php): Dihapus relasi `downPayment()`, kolom DP dari `$fillable` dan `$casts`, serta logika perhitungan `recalculatePaymentStatus()` disederhanakan murni berbasis pembayaran faktur (`PurchasePaymentItem`).
  - [Project](file:///c:/Users/itpta/material-monitoring/app/Models/Project.php): Dihapus relasi `purchaseDownPayments()`.
  - [PurchaseOrder](file:///c:/Users/itpta/material-monitoring/app/Models/PurchaseOrder.php): Dihapus relasi `downPayments()`.
  - [Supplier](file:///c:/Users/itpta/material-monitoring/app/Models/Supplier.php): Dihapus relasi `downPayments()`.
  - [User](file:///c:/Users/itpta/material-monitoring/app/Models/User.php): Dihapus method `canReadDownPayment()`, `canWriteDownPayment()`, permission `down_payment_read`, `down_payment_write`, dan preset permission pada semua role.
- **Controller & Rute**:
  - Dihapus: `app/Http/Controllers/PurchaseDownPaymentController.php`.
  - [ProcurementController](file:///c:/Users/itpta/material-monitoring/app/Http/Controllers/ProcurementController.php): Dihapus pemrosesan DP pada pembuatan invoice (`storeInvoice`) dan penghapusan invoice (`destroyInvoice`).
  - [SupplierController](file:///c:/Users/itpta/material-monitoring/app/Http/Controllers/SupplierController.php): Dihapus pengecekan relasi DP pada validasi hapus supplier.
  - [FinanceSummaryController](file:///c:/Users/itpta/material-monitoring/app/Http/Controllers/FinanceSummaryController.php): Dihapus pemanggilan relasi dan akumulasi DP pada ringkasan toko/supplier lintas proyek.
  - [web.php](file:///c:/Users/itpta/material-monitoring/routes/web.php): Dihapus rute `/finance/down-payments`.
- **Views**:
  - Dihapus folder & view: `resources/views/finance/down_payments/`.
  - [app.blade.php](file:///c:/Users/itpta/material-monitoring/resources/views/layouts/app.blade.php): Dihapus pengecekan `canReadDownPayment()`.
  - [payments/index.blade.php](file:///c:/Users/itpta/material-monitoring/resources/views/finance/payments/index.blade.php): Dihapus referensi `down_payment_amount` pada kalkulasi JavaScript sisa bayar.
- **Pengujian**:
  - Diperbarui: `FinanceAndPurchasingModulesTest` dan `SystemAuditAndRelationshipsTest` untuk menguji pelunasan murni tanpa DP.
