# Project Progress & Memory: Material-Monitoring

## Status Terakhir (2026-10-26)
- **Status Sesi**: Penghapusan total modul dan fungsi Down Payment (Uang Muka) selesai terverifikasi.
- **Hasil Akhir Test Suite**: `php artisan test --compact` -> **64 tests passed, 292 assertions** (0 failed, 0 regressions).
- **Hasil Akhir Asset Build**: `npm run build` -> Vite v8 build sukses (1.44s).
- **Code Style**: `vendor/bin/pint --format agent` -> 100% passed.

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
